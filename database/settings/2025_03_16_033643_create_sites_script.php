<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sites_scripts.header_scripts', '<!-- input your header script code here.-->');
        $this->migrator->add('sites_scripts.body_start_scripts', '<!-- input your body start script code here.-->');
        $this->migrator->add('sites_scripts.body_end_scripts', '<!-- input your body end script code here.-->');
        $this->migrator->add('sites_scripts.footer_scripts', '<!-- input your footer script code here.-->');
        $this->migrator->add('sites_scripts.cookie_consent_enabled', true);
        $this->migrator->add('sites_scripts.cookie_consent_text', 'We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.');
        $this->migrator->add('sites_scripts.cookie_consent_button_text', 'Accept');
        $this->migrator->add('sites_scripts.cookie_consent_policy_url', '/cookie-policy');
        $this->migrator->add('sites_scripts.custom_css', "/* input your custome css code here. */");
        $this->migrator->add('sites_scripts.custom_js', '/* input your custom js code here. */');
    }
};
