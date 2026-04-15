<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define all system clearances grouped by module
        $permissions = [
            
            // --- CGI DIRECTIVE (GENERATION) ---
            'view_cgi_index',         // Can view the main index table of all directives
            'access_cgi_generator',   // Can see the "New Directive" creation page
            'generate_images',        // Can spend credits/API to make images
            'generate_videos',        // Can spend credits/API to make videos
            'apply_branding',         // Can overlay logo on assets
            'view_branded_assets',    // Can see branded versions of assets
            
            // --- IMAGE GALLERY ---
            'view_image_gallery',     // Can see the image gallery page
            'download_images',        // Can download images to their PC
            'delete_images',          // Can delete images/directives from the database
            
            // --- VIDEO GALLERY ---
            'view_video_gallery',     // Can see the video gallery page
            'download_videos',        // Can download videos to their PC
            'delete_videos',          // Can delete videos from the database
            
            // --- SOCIAL & EXPORT ---
            'publish_to_social',      // Can auto-post to social media (future feature)
            'export_reports',         // Can download system usage reports

            // --- SYSTEM ADMINISTRATION ---
            'manage_agents',          // Can provision, edit, or delete users
            'manage_roles',           // Can create and configure Spatie roles
            'view_system_logs',
            'subscribe_to_packages',   // Can view system errors and logs
            'view_billing',           // Can access the billing dashboard
        ];

        foreach ($permissions as $permission) {
            // findOrCreate ensures we don't accidentally create duplicates if you run the seeder twice
            Permission::findOrCreate($permission);
        }
        
        $this->command->info('System clearances (Permissions) successfully injected into the neural net.');
    }
}