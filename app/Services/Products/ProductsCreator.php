<?php

namespace App\Services\Products;

use App\SkuColorReferences;
use Validator;
use Illuminate\Support\Facades\Log;
use App\Brand;
use App\Category;
use App\ColorNamesReference;
use App\Product;
use App\ProductStatus;
use App\ScrapActivity;
use App\Supplier;
use App\Helpers\ProductHelper;
use App\Helpers\StatusHelper;
use App\SupplierBrandCount;
use App\SupplierCategoryCount;
use App\Setting;

class ProductsCreator
{
    public function createProduct($image, $isExcel = 0)
    {
        // Debug log
        Log::channel('productUpdates')->debug("[Start] createProduct is called");

        // Set supplier
        $supplier = Supplier::leftJoin("scrapers as sc", "sc.supplier_id", "suppliers.id")->where(function ($query) use ($image) {
            $query->where('supplier', '=', $image->website)->orWhere('sc.scraper_name', '=', $image->website);
        })->first();

        // Do we have a supplier?
        if ($supplier == null) {
            // Debug
            Log::channel('productUpdates')->debug("[Error] Supplier is null " . $image->website);

            // Return false
            return false;
        } else {
            $supplierId = $supplier->id;
            $supplier = $supplier->supplier;
        }

        // Get formatted data
        $formattedPrices = $this->formatPrices($image);
        $formattedDetails = $this->getGeneralDetails($image->properties);

        // Set data.sku for validation
        $data[ 'sku' ] = ProductHelper::getSku($image->sku);
        $validator = Validator::make($data, [
            'sku' => 'unique:products,sku'
        ]);

        // Get color
        $color = ColorNamesReference::getProductColorFromObject($image);

        // Store count
        try {
            SupplierBrandCount::firstOrCreate(['supplier_id' => $supplierId, 'brand_id' => $image->brand_id]);
            if (!empty($formattedDetails[ 'category' ])) {
                SupplierCategoryCount::firstOrCreate(['supplier_id' => $supplierId, 'category_id' => $formattedDetails[ 'category' ]]);
            }
            if (!empty($color)) {
                SkuColorReferences::firstOrCreate(['brand_id' => $image->brand_id, 'color_name' => $color]);
            }
        } catch (\Exception $e) {
            // var_dump($e->getMessage());
        }

        // Product validated
        if ($validator->fails()) {
            // Debug
            Log::channel('productUpdates')->debug("[validator] fails - sku exists " . ProductHelper::getSku($image->sku));

            // Try to get the product from the database
            if($image->product_id > 0) {
                $product = Product::where('id', $image->product_id)->first();
            }else{
                $product = Product::where('sku', $data[ 'sku' ])->first();
            }

            // Does the product exist? This should not fail, since the validator told us it's there
            if (!$product) {
                // Debug
                Log::channel('productUpdates')->debug("[Error] No product!");

                // Return false
                return false;
            }

            // Is the product not approved yet?
            if (!StatusHelper::isApproved($image->status_id)) {
                // Check if we can update the title - not manually entered
                $manual = ProductStatus::where('name', 'MANUAL_TITLE')->where("product_id",$product->id)->first();
                if ($manual == null || (int)$manual->value == 0) {
                    $product->name = ProductHelper::getRedactedText($image->title, 'name');
                }

                // Check if we can update the short description - not manually entered
                $manual = ProductStatus::where('name', 'MANUAL_SHORT_DESCRIPTION')->where("product_id",$product->id)->first();
                if ($manual == null || (int)$manual->value == 0) {
                    $product->short_description = ProductHelper::getRedactedText($image->description, 'short_description');
                }

                // Check if we can update the color - not manually entered
                $manual = ProductStatus::where('name', 'MANUAL_COLOR')->where("product_id",$product->id)->first();
                if ($manual == null || (int)$manual->value == 0) {
                    $product->color = $color;
                }

                // Check if we can update the composition - not manually entered
                $manual = ProductStatus::where('name', 'MANUAL_COMPOSITION')->where("product_id",$product->id)->first();
                if ($manual == null || (int)$manual->value == 0) {
                    // Check for composition key
                    if (isset($image->properties[ 'composition' ])) {
                        $product->composition = trim(ProductHelper::getRedactedText($image->properties[ 'composition' ] ?? '', 'composition'));
                    }

                    // Check for material_used key
                    if (isset($image->properties[ 'material_used' ])) {
                        $product->composition = trim(ProductHelper::getRedactedText($image->properties[ 'material_used' ] ?? '', 'composition'));
                    }
                }

                $manual = ProductStatus::where('name', 'MANUAL_CATEGORY')->where("product_id",$product->id)->first();
                if ($manual == null || (int)$manual->value == 0) {
                    // Update the category
                    $product->category = $formattedDetails[ 'category' ];
                }
            }

            // Get current sizes
            $allSize = [];

            // Update with scraped sizes
            if (is_array($image->properties[ 'sizes' ]) && count($image->properties[ 'sizes' ]) > 0) {
                $sizes = $image->properties[ 'sizes' ];
                $euSize = [];

                // Loop over sizes and redactText
                if (is_array($sizes) && $sizes > 0) {
                    foreach ($sizes as $size) {
                        $helperSize = ProductHelper::getRedactedText($size, 'composition');
                        $allSize[] = $helperSize;
                        //find the eu size and update into the field
                        $euSize[]  = ProductHelper::getWebsiteSize($image->size_system, $helperSize, $product->category);
                    }
                }

                $product->size = implode(',', $allSize);
                $product->size_eu = implode(',', $euSize);
            }

            // Store measurement
            $product->lmeasurement = $formattedDetails[ 'lmeasurement' ] > 0 ? $formattedDetails[ 'lmeasurement' ] : null;
            $product->hmeasurement = $formattedDetails[ 'hmeasurement' ] > 0 ? $formattedDetails[ 'hmeasurement' ] : null;
            $product->dmeasurement = $formattedDetails[ 'dmeasurement' ] > 0 ? $formattedDetails[ 'dmeasurement' ] : null;
            $product->price = $formattedPrices[ 'price_eur' ];
            $product->price_eur_special = $formattedPrices[ 'price_eur_special' ];
            $product->price_eur_discounted = $formattedPrices[ 'price_eur_discounted' ];
            $product->price_inr = $formattedPrices[ 'price_inr' ];
            $product->price_inr_special = $formattedPrices[ 'price_inr_special' ];
            $product->price_inr_discounted = $formattedPrices[ 'price_inr_discounted' ];
            $product->is_scraped = $isExcel == 1 ? $product->is_scraped : 1;
            $product->save();
            $product->attachImagesToProduct();
            // check that if product has no title and everything then send to the external scraper
            $product->checkExternalScraperNeed();


            if ($image->is_sale) {
                $product->is_on_sale = 1;
                $product->save();
            }

            if ($db_supplier = Supplier::select('suppliers.id')->leftJoin("scrapers as sc", "sc.supplier_id", "suppliers.id")->where(function ($query) use ($supplier) {
                $query->where('supplier', '=', $supplier)->orWhere('sc.scraper_name', '=', $supplier);
            })->first()) {
                if ($product) {

                    $productSupplier = \App\ProductSupplier::where("supplier_id",$db_supplier->id)->where("product_id",$product->id)->first();
                    if(!$productSupplier)  {
                        $productSupplier = new \App\ProductSupplier;
                        $productSupplier->supplier_id = $db_supplier->id;
                        $productSupplier->product_id = $product->id;
                    }

                    $productSupplier->title = $image->title;
                    $productSupplier->description = $image->description;
                    $productSupplier->supplier_link = $image->url;
                    $productSupplier->stock = 1;
                    $productSupplier->price = $formattedPrices[ 'price_eur' ];
                    $productSupplier->price_special = $formattedPrices[ 'price_eur_special' ];
                    $productSupplier->price_discounted = $formattedPrices[ 'price_eur_discounted' ];
                    $productSupplier->size = $formattedDetails[ 'size' ];
                    $productSupplier->color = $formattedDetails[ 'color' ];
                    $productSupplier->composition = $formattedDetails[ 'composition' ];
                    $productSupplier->sku = $image->original_sku;
                    $productSupplier->save();


                    /*$product->suppliers()->syncWithoutDetaching([
                        $db_supplier->id => [
                            'title' => $image->title,
                            'description' => $image->description,
                            'supplier_link' => $image->url,
                            'stock' => 1,
                            'price' => $formattedPrices[ 'price_eur' ],
                            'price_special' => $formattedPrices[ 'price_eur_special' ],
                            'price_discounted' => $formattedPrices[ 'price_eur_discounted' ],
                            'size' => $formattedDetails[ 'size' ],
                            'color' => $formattedDetails[ 'color' ],
                            'composition' => $formattedDetails[ 'composition' ],
                            'sku' => $image->original_sku
                        ]
                    ]);*/
                }
            }

            $dup_count = 0;
            $supplier_prices = [];

            foreach ($product->suppliers_info as $info) {
                if ($info->price != '') {
                    $supplier_prices[] = $info->price;
                }
            }

            foreach (array_count_values($supplier_prices) as $price => $c) {
                $dup_count++;
            }

            if ($dup_count > 1) {
                $product->is_price_different = 1;
            } else {
                $product->is_price_different = 0;
            }

            $product->stock += 1;
            $product->save();

            $supplier = $image->website;

            $params = [
                'website' => $supplier,
                'scraped_product_id' => $product->id,
                'status' => 1
            ];

            //ScrapActivity::create($params);

            Log::channel('productUpdates')->debug("[Success] Updated product");

            return;

        } else {
            Log::channel('productUpdates')->debug("[validator] success - new sku " . ProductHelper::getSku($image->sku));
            $product = new Product;
        }

        if ($product === null) {
            Log::channel('productUpdates')->debug("[Skipped] Product is null");
            return;
        }
        // Changed status to auto crop now
        $product->status_id = \App\Helpers\StatusHelper::$autoCrop;
        $product->sku = str_replace(' ', '', $image->sku);
        $product->brand = $image->brand_id;
        $product->supplier = $supplier;
        $product->name = $image->title;
        $product->short_description = $image->description;
        $product->supplier_link = $image->url;
        $product->stage = 3;
        $product->is_scraped = $isExcel == 1 ? 0 : 1;
        $product->stock = 1;
        $product->is_without_image = 1;
        $product->is_on_sale = $image->is_sale ? 1 : 0;

        $product->composition = $formattedDetails[ 'composition' ];
        $product->color = ColorNamesReference::getProductColorFromObject($image);
        $product->size = $formattedDetails[ 'size' ];
        $product->lmeasurement = (int)$formattedDetails[ 'lmeasurement' ];
        $product->hmeasurement = (int)$formattedDetails[ 'hmeasurement' ];
        $product->dmeasurement = (int)$formattedDetails[ 'dmeasurement' ];
        $product->measurement_size_type = $formattedDetails[ 'measurement_size_type' ];
        $product->made_in = $formattedDetails[ 'made_in' ];
        $product->category = $formattedDetails[ 'category' ];
        // start to update the eu size
        if(!empty($product->size)) {
            $sizeExplode = explode(",", $product->size);
            if(!empty($sizeExplode) && is_array($sizeExplode)){
                $euSize = [];
                foreach($sizeExplode as $sizeE){
                    $helperSize = ProductHelper::getRedactedText($sizeE, 'composition');
                    //find the eu size and update into the field
                    $euSize[]  = ProductHelper::getWebsiteSize($image->size_system, $helperSize, $product->category);
                }
                if(!empty($euSize)) {
                    $product->size_eu = implode(',', $euSize);
                }
            }
        }

        $product->price = $formattedPrices[ 'price_eur' ];
        $product->price_eur_special = $formattedPrices[ 'price_eur_special' ];
        $product->price_eur_discounted = $formattedPrices[ 'price_eur_discounted' ];
        $product->price_inr = $formattedPrices[ 'price_inr' ];
        $product->price_inr_special = $formattedPrices[ 'price_inr_special' ];
        $product->price_inr_discounted = $formattedPrices[ 'price_inr_discounted' ];

        try {
            $product->save();
            $image->product_id = $product->id;
            $image->save();
            $product->attachImagesToProduct();

            // check that if product has no title and everything then send to the external scraper
            $product->checkExternalScraperNeed();

            Log::channel('productUpdates')->debug("[New] Product created with ID " . $product->id);
        } catch (\Exception $exception) {
            Log::channel('productUpdates')->alert("[Exception] Couldn't create product");
            Log::channel('productUpdates')->alert($exception->getMessage());
            return;
        }

        if ($db_supplier = Supplier::select('suppliers.id')->leftJoin("scrapers as sc", "sc.supplier_id", "suppliers.id")->where(function ($query) use ($supplier) {
            $query->where('supplier', '=', $supplier)->orWhere('sc.scraper_name', '=', $supplier);
        })->first()) {

            $productSupplier = \App\ProductSupplier::where("supplier_id",$db_supplier->id)->where("product_id",$product->id)->first();
            if(!$productSupplier)  {
                $productSupplier = new \App\ProductSupplier;
                $productSupplier->supplier_id = $db_supplier->id;
                $productSupplier->product_id = $product->id;
            }

            $productSupplier->title = $image->title;
            $productSupplier->description = $image->description;
            $productSupplier->supplier_link = $image->url;
            $productSupplier->stock = 1;
            $productSupplier->price = $formattedPrices[ 'price_eur' ];
            $productSupplier->price_special = $formattedPrices[ 'price_eur_special' ];
            $productSupplier->price_discounted = $formattedPrices[ 'price_eur_discounted' ];
            $productSupplier->size = $formattedDetails[ 'size' ];
            $productSupplier->color = $formattedDetails[ 'color' ];
            $productSupplier->composition = $formattedDetails[ 'composition' ];
            $productSupplier->sku = $image->original_sku;
            $productSupplier->save();

            /*$product->suppliers()->syncWithoutDetaching([
                $db_supplier->id => [
                    'title' => $image->title,
                    'description' => $image->description,
                    'supplier_link' => $image->url,
                    'stock' => 1,
                    'price' => $formattedPrices[ 'price_eur' ],
                    'price_special' => $formattedPrices[ 'price_eur_special' ],
                    'price_discounted' => $formattedPrices[ 'price_eur_discounted' ],
                    'size' => $formattedDetails[ 'size' ],
                    'color' => $formattedDetails[ 'color' ],
                    'composition' => $formattedDetails[ 'composition' ],
                    'sku' => $image->original_sku
                ]
            ]);*/
        }
    }

    public function formatPrices($image)
    {
        // Get brand from database
        $brand = Brand::find($image->brand_id);

        // Check for EUR to INR
        if (!empty($brand->euro_to_inr)) {
            $priceInr = (float)$brand->euro_to_inr * (float)trim($image->price);
        } else {
            $priceInr = (float)Setting::get('euro_to_inr') * (float)trim($image->price);
        }

        // Set INR price
        $priceInr = round($priceInr, -3);

        if (!empty($image->price) && !empty($priceInr)) {
            $priceEurSpecial = $image->price - ($image->price * $brand->deduction_percentage) / 100;
            $priceInrSpecial = $priceInr - ($priceInr * $brand->deduction_percentage) / 100;
        } else {
            $priceEurSpecial = '';
            $priceInrSpecial = '';
        }

        // Product on sale?
        if ($image->is_sale == 1 && $brand->sales_discount > 0 && !empty($priceEurSpecial)) {
            $priceEurDiscounted = $priceEurSpecial - ($priceEurSpecial * $brand->sales_discount) / 100;
            $priceInrDiscounted = $priceInrSpecial - ($priceInrSpecial * $brand->sales_discount) / 100;
        } else {
            $priceEurDiscounted = 0;
            $priceInrDiscounted = 0;
        }

        // Return prices
        return [
            'price_eur' => $image->price,
            'price_eur_special' => $priceEurSpecial,
            'price_eur_discounted' => $priceEurDiscounted,
            'price_inr' => $priceInr,
            'price_inr_special' => $priceInrSpecial,
            'price_inr_discounted' => $priceInrDiscounted
        ];
    }

    public function getGeneralDetails($properties_array)
    {
        if (array_key_exists('material_used', $properties_array)) {
            $composition = (is_array($properties_array[ 'material_used' ])) ? implode(" ",$properties_array[ 'material_used' ]) : (string)$properties_array[ 'material_used' ];
        }

        if (array_key_exists('color', $properties_array)) {
            $color = $properties_array[ 'color' ];
        }

        if (array_key_exists('sizes', $properties_array)) {
            $orgSizes = $properties_array[ 'sizes' ];
            $tmpSizes = [];

            // Loop over sizes
            foreach ($orgSizes as $size) {
                if (substr(strtoupper($size), -2) == 'IT') {
                    $size = str_replace('IT', '', $size);
                    $size = trim($size);
                }

                if (!empty($size)) {
                    $tmpSizes[] = $size;
                }
            }

            $size = implode(',', $tmpSizes);
        }

        if (array_key_exists('dimension', $properties_array)) {
            if (is_array($properties_array[ 'dimension' ])) {
                $exploded = $properties_array[ 'dimension' ];
                if (count($exploded) > 0) {
                    if (array_key_exists('0', $exploded)) {
                        $lmeasurement = (int)$exploded[ 0 ];
                        $measurement_size_type = 'measurement';
                    }

                    if (array_key_exists('1', $exploded)) {
                        $hmeasurement = (int)$exploded[ 1 ];
                    }

                    if (array_key_exists('2', $exploded)) {
                        $dmeasurement = (int)$exploded[ 2 ];
                    }
                }
            }
        }

        // Get category
        if (array_key_exists('category', $properties_array)) {
            // Check if category is an array
            if (is_array($properties_array[ 'category' ])) {
                // Set gender to null
                $gender = null;

                // Loop over categories to find gender
                foreach ($properties_array[ 'category' ] as $category) {
                    // Check for gender man
                    if (in_array(strtoupper($category), ['MAN', 'MEN', 'UOMO', 'MALE'])) {
                        $gender = 'MEN';
                    }

                    // Check for gender woman
                    if (in_array(strtoupper($category), ['WOMAN', 'WOMEN', 'DONNA', 'FEMALE'])) {
                        $gender = 'WOMEN';
                    }
                }

                // Try to get category ID
                $category = Category::getCategoryIdByKeyword(end($properties_array[ 'category' ]), $gender);
            }
        }

        if (array_key_exists('country', $properties_array)) {
            $made_in = $properties_array[ 'country' ];
        }

        return [
            'composition' => isset($composition) ? $composition : '',
            'color' => isset($color) ? $color : '',
            'size' => isset($size) ? $size : '',
            'lmeasurement' => isset($lmeasurement) ? $lmeasurement : '',
            'hmeasurement' => isset($hmeasurement) ? $hmeasurement : '',
            'dmeasurement' => isset($dmeasurement) ? $dmeasurement : '',
            'measurement_size_type' => isset($measurement_size_type) ? $measurement_size_type : '',
            'made_in' => isset($made_in) ? $made_in : '',
            'category' => isset($category) ? $category : 1,
        ];
    }
}
