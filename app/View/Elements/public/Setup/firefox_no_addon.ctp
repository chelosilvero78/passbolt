<div class="plugin-check-wrapper">
	<h3><?php echo __('Plugin check') ?></h3>
	<?php echo $this->element('public/plugin/firefox-no-addon'); ?>
</div>
<div class="why-plugin-wrapper">
	<h3><?php echo("Why do I need a plugin"); ?></h3>
	<p>
		<?php echo __('Passbolt requires a browser add-on to guarantee that your secret key and your master password are never
			accessible to any website (including passbolt.com itself). This is also the only way to guarantee that
			the core cryptographic libraries cannot be tampered with.'); ?>
	</p>
</div>
<div class="submit-input-wrapper">
	<a id="js_setup_plugin_check" href="#" class="button primary big">retry</a>
</div>
