<h3>{{$title}}</h3>
{{if $test}}
	{{foreach $items as $i}}
		<p>
			{{$i.id}}
			text={{$i.body}}
			coordinates={{$i.coord}}
		</p>
	{{/foreach}}
{{/if}}

{{if $form}}
<form name="routes_form" id="routes_form" method="get" action="{{$path}}/submit/">
{{if $id}}
<input name="r_id_inp" id="r_id_inp" value="{{$id}}" type="hidden">
{{/if}}
<div>
<label for="r_name_inp">
Name: 
</label>
<input name="r_name_inp" id="r_name_inp" value="{{$name}}">
</div>
<div>
<input name="r_submit" id="r_submit" value="Add/Change" type="submit">
</div>
</form>
{{/if}}

{{if $routes_list}}
<table>
<tr>
<th>
id
</th>
<th>
name
</th>
<th>
active
</th>
<th>
remove
</th>
</tr>
	{{foreach $routes as $i}}
		<tr>
			<td>
				{{$i.id}}
			</td>
			<td>
			<a href="{{$path}}/alter/?r_id={{$i.id}}">
				{{$i.name}}
				</a>
			</td>
			<td>
			{{if $i.active}}
			<a href="{{$path}}/deactivate/?r_id={{$i.id}}">
			deactivate
			</a>
			{{else}}
			<a href="{{$path}}/activate/?r_id={{$i.id}}">
			activate
			</a>
			{{/if}}
			</td>
			<td>
			<a href="{{$path}}/remove/?r_id={{$i.id}}">
				remove
				</a>
			</td>
		</tr>
	{{/foreach}}
</table>
{{/if}}