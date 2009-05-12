<form id="form-md5" action="<?= Tyke::get('core.run.self') ?>" method="post">

	<fieldset>
		<legend>Generate MD5 from Text</legend>

		<?php if (!empty($errors)): ?>
		<ul>
			<?php foreach ($errors as $error): ?>
			<li><?= htmlspecialchars($error) ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<label>
			<span>Text:</span>
			<input type="text" name="text" value="<?= empty($_POST['text']) ? '' : htmlspecialchars($_POST['text']) ?>" />
		</label>
		= <tt id="result"><?= isset($md5) ? $md5 : '?' ?></tt>

		<p>Form is submitted via Ajax (MooTools Request). Disable JS to see normal behaviour, it degrades gracefully.</p>

		<div>
			<input type="submit" value="Generate" />
		</div>

	</fieldset>
</form>
<script type="text/javascript">
	$('form-md5').addEvent('submit', function() {

		new Request({
			onComplete: function() {
				var json = JSON.decode(this.response.text, true);
				if (!json) {
					alert('Something Failed');
				} else {
					if (json.errors) alert('Invalid Request:\n * ' + json.errors.join('\n * '));
					else $('result').set('text', json.result).highlight();
				}
			}
		}).send({
			data: this,
			method: 'post',
			url: this.action,

		});

		return false;
	});
</script>