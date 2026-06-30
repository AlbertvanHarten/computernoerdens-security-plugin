<?php

namespace Computernoerden\Security\Admin;

defined('ABSPATH') || exit;

/**
 * Registers the WordPress admin interface.
 */
class Admin
{
    /**
     * Register admin hooks.
     *
     * @return void
     */
    public function boot()
    {
        add_action('admin_menu', array($this, 'registerMenu'));
    }

    /**
     * Register plugin menu.
     *
     * @return void
     */
    public function registerMenu()
    {
        add_menu_page(
            "Computernørden's Security Plugin",
            'Computernørden',
            'manage_options',
            'computernoerdens-security-plugin',
            array($this, 'renderSecurityCenter'),
            'dashicons-shield-alt',
            58
        );
    }

    /**
     * Render Security Center.
     *
     * @return void
     */
    public function renderSecurityCenter()
    {
        echo '<div class="wrap">';
        echo '<h1>Computernørden\'s Security Plugin</h1>';
        echo '<h2>Security Center</h2>';
        echo '<p>The Security Center is alive.</p>';
        echo '</div>';
    }
}