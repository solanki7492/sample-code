<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::prefix('digital-marketing')->group(function () {
    Route::get('/', 'DigitalMarketingController@index')->name('digital-marketing.index');
    Route::get('/records', 'DigitalMarketingController@records')->name('digital-marketing.records');
    Route::post('/save', 'DigitalMarketingController@save')->name('digital-marketing.save');
    Route::prefix('{id}')->group(function () {
        Route::get('/edit', 'DigitalMarketingController@edit')->name("digital-marketing.edit");
        Route::get('/components', 'DigitalMarketingController@components')->name("digital-marketing.components");
        Route::post('/components', 'DigitalMarketingController@componentStore')->name("digital-marketing.components.save");
        Route::get('/delete', 'DigitalMarketingController@delete')->name("digital-marketing.delete");

        Route::prefix('solution')->group(function () {
            Route::get('/', 'DigitalMarketingController@solution')->name("digital-marketing.solutions");
            Route::get('/records', 'DigitalMarketingController@solutionRecords')->name("digital-marketing.records");
            Route::post('/save', 'DigitalMarketingController@solutionSave')->name("digital-marketing.solution.save");
            Route::post('/create-usp', 'DigitalMarketingController@solutionCreateUsp')->name("digital-marketing.solution.create-usp");
            Route::prefix('{solutionId}')->group(function () {
                Route::get('/edit', 'DigitalMarketingController@solutionEdit')->name("digital-marketing.solution.edit");
                Route::get('/delete', 'DigitalMarketingController@solutionDelete')->name("digital-marketing.solution.delete");
                Route::post('/save-usp', 'DigitalMarketingController@solutionSaveUsp')->name("digital-marketing.solution.delete");
                Route::prefix('research')->group(function () {
                    Route::get('/', 'DigitalMarketingController@research')->name("digital-marketing.solution.research");
                    Route::get('/records', 'DigitalMarketingController@researchRecords')->name("digital-marketing.solution.research");
                    Route::post('/save', 'DigitalMarketingController@researchSave')->name("digital-marketing.solution.research.save");
                    Route::prefix('{researchId}')->group(function () {
                        Route::get('/edit', 'DigitalMarketingController@researchEdit')->name("digital-marketing.solution.research.edit");
                        Route::get('/delete', 'DigitalMarketingController@researchDelete')->name("digital-marketing.solution.research.delete");
                    });
                });     

            });        
        });    
    }); 
});
