<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $webDesign = BlogCategory::where('name', 'Web Design')->first();
        $marketing = BlogCategory::where('name', 'Digital Marketing')->first();
        $news = BlogCategory::where('name', 'Industry News')->first();
        $author = User::where('email', 'test@example.com')->first() ?? User::first();

        $posts = [
            [
                'blog_category_id' => $webDesign?->id,
                'title' => '7 Web Design Trends Shaping Client Expectations in 2026',
                'content' => "Clients are asking for faster, more accessible, and more personalized websites than ever before. In this post we break down the seven design trends we're seeing across our project pipeline: motion-driven storytelling, accessible-by-default color systems, modular content blocks, dark-mode-first palettes, AI-assisted personalization, micro-interactions that guide rather than distract, and a return to bold, readable typography.\n\nEach of these trends reflects a shift toward websites that do more work for the visitor with less friction. We'll walk through real examples from recent client engagements and explain how our design team evaluates which trends are worth adopting versus which are just noise.",
                'status' => 'published',
            ],
            [
                'blog_category_id' => $webDesign?->id,
                'title' => 'Why We Moved to a Component-Based Design System',
                'content' => "Building every project from scratch was slowing our delivery timelines and creating inconsistency across client sites. Six months ago we invested in a shared component library covering navigation, forms, cards, and content sections.\n\nThe result: a 30% reduction in front-end build time and far fewer visual bugs reaching QA. This post walks through how we structured the library, how designers and developers collaborate on it, and the lessons learned from the migration.",
                'status' => 'draft',
            ],
            [
                'blog_category_id' => $marketing?->id,
                'title' => 'A Practical Guide to Local SEO for Service Businesses',
                'content' => "Local search is often the highest-ROI channel for service-based businesses, yet it's frequently neglected in favor of broader SEO plays. In this guide we cover Google Business Profile optimization, review generation workflows, local citation building, and how to structure location pages that actually rank.\n\nWe also share a simple monthly checklist our team uses to keep client local listings accurate and competitive.",
                'status' => 'published',
            ],
            [
                'blog_category_id' => $marketing?->id,
                'title' => 'Email Marketing Benchmarks We Track for Every Client',
                'content' => "Not all open rates and click-through rates are created equal — industry, list size, and send cadence all shift what 'good' looks like. We maintain a running benchmark sheet across our client base and use it to set realistic targets for new campaigns.\n\nThis post shares anonymized benchmark ranges by industry along with the three metrics we think are underrated: unsubscribe rate trend, reply rate, and revenue per email.",
                'status' => 'published',
            ],
            [
                'blog_category_id' => $news?->id,
                'title' => 'What Recent Platform Algorithm Changes Mean for Agencies',
                'content' => "Search and social platforms have rolled out several significant algorithm updates this quarter. We summarize what changed, which client accounts were affected, and how our team adjusted content and technical strategy in response.\n\nThe short version: quality signals and first-party data are becoming more important than ever, while purely volume-based tactics are losing effectiveness.",
                'status' => 'draft',
            ],
            [
                'blog_category_id' => $news?->id,
                'title' => 'Our Take on the Growing Role of AI Tools in Agency Workflows',
                'content' => "AI-assisted tools are now part of our research, copywriting, and QA workflows — but not in the way most headlines suggest. We use them to accelerate first drafts and catch errors, while every deliverable still goes through human strategy and review.\n\nThis post is an honest look at where these tools save us time, where they still fall short, and how we're training our team to use them responsibly.",
                'status' => 'published',
            ],
        ];

        foreach ($posts as $index => $post) {
            $publishedAt = $post['status'] === 'published' ? now()->subDays(30 - ($index * 4)) : null;

            // Set slug explicitly rather than relying on BlogPost's
            // creating-event auto-slug: DatabaseSeeder uses
            // WithoutModelEvents, which silently skips that hook and would
            // otherwise insert with no slug (NOT NULL violation on MySQL).
            BlogPost::create(array_merge($post, [
                'slug' => BlogPost::generateUniqueSlug($post['title']),
                'author_id' => $author?->id,
                'published_at' => $publishedAt,
                'seo_title' => $post['title'],
                'seo_description' => \Illuminate\Support\Str::limit(strip_tags($post['content']), 155),
            ]));
        }
    }
}
