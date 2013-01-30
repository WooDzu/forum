{{ content() }}

<div class="start-discussion">

	<div align="left">
		<h1>Edit Discussion: {{ post.title|e }}</h1>
	</div>

	<div class="row">
		<div class="span1">
			<img src="https://secure.gravatar.com/avatar/{{ session.get('identity-gravatar') }}?s=48" class="img-rounded">
		</div>
		<div class="span9">
			<form method="post" autocomplete="off">

				<p>
					{{ hidden_field("id") }}
				</p>

				<p>
					{{ text_field("title", "placeholder": "Title") }}
				</p>

				<p>
					{{ select("categoryId", categories, 'using': ['id', 'name']) }}
				</p>

				<p>
					{{ text_area("content", "rows": 15, "placeholder": "Leave the content") }}
				</p>

				<p>
					<div align="left">
						{{ link_to('discussion/' , 'Cancel') }}
					</div>
					<div align="right">
						<button type="submit" class="btn btn-success">Save</button>
					</div>
			  	</p>

			</form>
		</div>
	</div>
</div>