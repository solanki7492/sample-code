<script type="text/x-jsrender" id="template-result-block">
	<div class="row">
		<table class="table table-bordered">
		    <thead>
		      <tr>
		      	<th width="2%">Id</th>
		        <th width="10%">Platform</th>
		        <th width="10%">Sub Platform</th>
		        <th width="20%">Components</th>
		        <th width="20%">Description</th>
		        <th width="8%">Status</th>
		        <th width="8%">Created At</th>
		        <th width="18%">Action</th>
		      </tr>
		    </thead>
		    <tbody>
		    	{{props data}}
			      <tr>
			      	<td>{{:prop.id}}</td>
			        <td>{{:prop.platform}}</td>
			        <td>{{:prop.sub_platform}}</td>
			        <td>{{:prop.components_list}}</td>
			        <td>{{:prop.description}}</td>
			        <td>{{:prop.status_name}}</td>
			        <td>{{:prop.created_at}}</td>
			        <td>
			        	<button type="button" data-id="{{>prop.id}}" class="btn btn-edit-template"><img width="15px" title="Edit" src="<?php echo asset('/images/edit.png') ?>"></button>
			        	|<button type="button" data-id="{{>prop.id}}" class="btn btn-delete-template"><i class="fa fa-trash" aria-hidden="true"></i></button>
			        </td>
			      </tr>
			    {{/props}}  
		    </tbody>
		</table>
		{{:pagination}}
	</div>
</script>
