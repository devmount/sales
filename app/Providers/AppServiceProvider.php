<?php

namespace App\Providers;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Filament only saves valid data to models so the models can be unguarded safely
        Model::unguard();

        // Customize Filament colors
        FilamentColor::register([
            'teal' => Color::Teal,
            'blue' => Color::Blue,
            'purple' => Color::Purple,
            'rose' => Color::Rose,
        ]);

        // Customize Filament icons
        FilamentIcon::register([
            // Panel Builder icon aliases
            // 'panels::global-search.field' => 'tabler-', // Global search field
            // 'panels::pages.dashboard.navigation-item' => 'tabler-', // Dashboard navigation item
            // 'panels::pages.tenancy.register-tenant.open-tenant-button' => 'tabler-', // Button to open a tenant from the tenant registration page
            // 'panels::sidebar.collapse-button' => 'tabler-', // Button to collapse the sidebar
            // 'panels::sidebar.expand-button' => 'tabler-', // Button to expand the sidebar
            // 'panels::sidebar.group.collapse-button' => 'tabler-', // Collapse button for a sidebar group
            // 'panels::tenant-menu.toggle-button' => 'tabler-', // Button to toggle the tenant menu
            // 'panels::theme-switcher.light-button' => 'tabler-', // Button to switch to the light theme from the theme switcher
            // 'panels::theme-switcher.dark-button' => 'tabler-', // Button to switch to the dark theme from the theme switcher
            // 'panels::theme-switcher.system-button' => 'tabler-', // Button to switch to the system theme from the theme switcher
            // 'panels::topbar.close-sidebar-button' => 'tabler-', // Button to close the sidebar
            // 'panels::topbar.open-sidebar-button' => 'tabler-', // Button to open the sidebar
            // 'panels::topbar.open-database-notifications-button' => 'tabler-', // Button to open the database notifications modal
            // 'panels::user-menu.profile-item' => 'tabler-', // Profile item in the user menu
            // 'panels::user-menu.logout-button' => 'tabler-', // Button in the user menu to log out
            // 'panels::widgets.account.logout-button' => 'tabler-', // Button in the account widget to log out
            // 'panels::widgets.filament-info.open-documentation-button' => 'tabler-', // Button to open the documentation from the Filament info widget
            // 'panels::widgets.filament-info.open-github-button' => 'tabler-', // Button to open GitHub from the Filament info widget

            // Form Builder icon aliases
            // 'forms:components.checkbox-list.search-field' => 'tabler-', // Search input in a checkbox list
            // 'forms::components.wizard.completed-step' => 'tabler-', // Completed step in a wizard

            // Table Builder icon aliases
            // 'tables::columns.collapse-button' => 'tabler-columns-3',
            // 'tables::filters.remove-all-button' => 'tabler-', // Button to remove all filters
            // 'tables::grouping.collapse-button' => 'tabler-', // Button to collapse a group of records
            'tables::header-cell.sort-asc-button' => 'tabler-chevron-up', // Sort button of a column sorted in ascending order
            'tables::header-cell.sort-desc-button' => 'tabler-chevron-down', // Sort button of a column sorted in descending order
            // 'tables::reorder.handle' => 'tabler-', // Handle to grab in order to reorder a record with drag and drop
            'tables::search-field' => 'tabler-search', // Search input

            // Notifications icon aliases
            // 'notifications::database.modal.empty-state' => 'tabler-ban', // Empty state of the database notifications modal
            // 'notifications::notification.close-button' => 'tabler-', // Button to close a notification

            // UI components icon aliases
            // 'badge.delete-button' => 'tabler-', // Button to delete a badge
            'breadcrumbs.separator' => 'tabler-chevron-right', // Separator between breadcrumbs
            'modal.close-button' => 'tabler-x', // Button to close a modal
            'pagination.previous-button' => 'tabler-chevron-left', // Button to go to the previous page
            'pagination.next-button' => 'tabler-chevron-right', // Button to go to the next page
            // 'section.collapse-button' => 'tabler-', // Button to collapse a section
        ]);
    }
}
