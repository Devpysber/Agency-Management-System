<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'client_name' => 'Sarah Whitman',
                'company' => 'Northbridge Holdings',
                'message' => 'Working with this team transformed our online presence. The website redesign exceeded our expectations, and our conversion rate has nearly doubled since launch.',
                'rating' => 5,
                'status' => 'approved',
            ],
            [
                'client_name' => 'Marcus Delgado',
                'company' => 'Willow & Bean Cafe',
                'message' => 'The branding package they created captured exactly the warm, inviting feel we wanted for our cafe. Customers constantly compliment our new logo and packaging.',
                'rating' => 5,
                'status' => 'approved',
            ],
            [
                'client_name' => 'Priya Nair',
                'company' => 'Marlowe Goods Co.',
                'message' => 'From planning to launch, the e-commerce build was handled professionally. There were a few bumps during the migration, but the team resolved them quickly.',
                'rating' => 4,
                'status' => 'approved',
            ],
            [
                'client_name' => 'James Okoro',
                'company' => 'Vantage Fitness',
                'message' => 'Our social media campaign assets were delivered on time and looked fantastic. Engagement on our launch posts was noticeably higher than previous campaigns.',
                'rating' => 4,
                'status' => 'pending',
            ],
            [
                'client_name' => 'Elena Rossi',
                'company' => 'Pulse Health',
                'message' => 'The app redesign was solid overall, though we had to request a few rounds of revisions before the navigation flow felt right. Support was responsive throughout.',
                'rating' => 3,
                'status' => 'pending',
            ],
            [
                'client_name' => 'Tobias Reyes',
                'company' => 'Ferro Industrial Supply',
                'message' => 'Great communication and a genuinely creative approach to our trade show materials. Booth traffic was up significantly this year thanks to their design work.',
                'rating' => 5,
                'status' => 'approved',
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }
    }
}
