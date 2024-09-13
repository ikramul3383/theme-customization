<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit64643545433cb2c75068ad130ad28183
{
    public static $classMap = array (
        'WPML_Compatibility_MailChimp' => __DIR__ . '/../..' . '/classes/class-wpml-compatibility-mailchimp.php',
        'WPML_Compatibility_MailChimp_Redirection' => __DIR__ . '/../..' . '/classes/class-wpml-compatibility-mailchimp-redirection.php',
        'WPML_Compatibility_MailChimp_Shortcode_Attributes_Filter' => __DIR__ . '/../..' . '/classes/class-wpml-compatibility-mailchimp-shortcode-attributes-filter.php',
        'WPML_Compatibility_MailChimp_Widget_Filter' => __DIR__ . '/../..' . '/classes/class-wpml-compatibility-mailchimp-widget-filter.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit64643545433cb2c75068ad130ad28183::$classMap;

        }, null, ClassLoader::class);
    }
}
