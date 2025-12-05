<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'subcategories' => [
                    'Software Development',
                    'Web Development',
                    'Mobile Apps',
                    'Artificial Intelligence',
                    'Cybersecurity',
                    'Cloud Computing',
                    'Data Science',
                    'Blockchain & Crypto',
                    'Gadgets & Reviews',
                    'Tech News',
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'subcategories' => [
                    'Entrepreneurship',
                    'Small Business',
                    'Startups',
                    'Marketing',
                    'Sales',
                    'Management',
                    'Finance & Accounting',
                    'E-commerce',
                    'Business Strategy',
                    'Leadership',
                ],
            ],
            [
                'name' => 'Health & Fitness',
                'slug' => 'health-fitness',
                'subcategories' => [
                    'Fitness & Workouts',
                    'Nutrition & Diet',
                    'Mental Health',
                    'Weight Loss',
                    'Yoga & Meditation',
                    'Supplements',
                    'Medical Health',
                    'Wellness',
                    'Sports Medicine',
                    'Healthy Living',
                ],
            ],
            [
                'name' => 'Finance',
                'slug' => 'finance',
                'subcategories' => [
                    'Personal Finance',
                    'Investing',
                    'Cryptocurrency',
                    'Banking',
                    'Insurance',
                    'Real Estate Investing',
                    'Retirement Planning',
                    'Tax Planning',
                    'Credit & Debt',
                    'Financial Planning',
                ],
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
                'subcategories' => [
                    'Online Learning',
                    'Higher Education',
                    'K-12 Education',
                    'Professional Development',
                    'Language Learning',
                    'Tutoring',
                    'Educational Technology',
                    'Study Tips',
                    'Career Development',
                    'Certifications',
                ],
            ],
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'subcategories' => [
                    'Travel Destinations',
                    'Travel Tips',
                    'Hotels & Accommodations',
                    'Adventure Travel',
                    'Budget Travel',
                    'Luxury Travel',
                    'Travel Guides',
                    'Travel Photography',
                    'Cruises',
                    'Travel Planning',
                ],
            ],
            [
                'name' => 'Food & Cooking',
                'slug' => 'food-cooking',
                'subcategories' => [
                    'Recipes',
                    'Cooking Tips',
                    'Restaurant Reviews',
                    'Baking',
                    'Healthy Recipes',
                    'Vegetarian & Vegan',
                    'Food Photography',
                    'Wine & Beverages',
                    'Food Trends',
                    'Culinary Arts',
                ],
            ],
            [
                'name' => 'Fashion & Beauty',
                'slug' => 'fashion-beauty',
                'subcategories' => [
                    'Fashion Trends',
                    'Beauty Tips',
                    'Makeup Tutorials',
                    'Skincare',
                    'Hair Care',
                    'Style Guides',
                    'Fashion Design',
                    'Beauty Products',
                    'Sustainable Fashion',
                    'Fashion Accessories',
                ],
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'subcategories' => [
                    'Football',
                    'Basketball',
                    'Soccer',
                    'Baseball',
                    'Tennis',
                    'Golf',
                    'Fitness Sports',
                    'Sports News',
                    'Sports Betting',
                    'Sports Equipment',
                ],
            ],
            [
                'name' => 'Entertainment',
                'slug' => 'entertainment',
                'subcategories' => [
                    'Movies & TV Shows',
                    'Music',
                    'Gaming',
                    'Celebrity News',
                    'Comedy',
                    'Books & Literature',
                    'Podcasts',
                    'Streaming',
                    'Theater',
                    'Entertainment News',
                ],
            ],
            [
                'name' => 'Real Estate',
                'slug' => 'real-estate',
                'subcategories' => [
                    'Home Buying',
                    'Home Selling',
                    'Real Estate Investing',
                    'Property Management',
                    'Home Improvement',
                    'Interior Design',
                    'Architecture',
                    'Real Estate Market',
                    'Commercial Real Estate',
                    'Real Estate Agents',
                ],
            ],
            [
                'name' => 'Automotive',
                'slug' => 'automotive',
                'subcategories' => [
                    'Car Reviews',
                    'Auto Maintenance',
                    'Car Buying Guide',
                    'Electric Vehicles',
                    'Motorcycles',
                    'Auto Racing',
                    'Car Accessories',
                    'Truck & SUV',
                    'Classic Cars',
                    'Automotive News',
                ],
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'subcategories' => [
                    'Home Decor',
                    'Gardening',
                    'DIY Projects',
                    'Home Organization',
                    'Furniture',
                    'Landscaping',
                    'Home Renovation',
                    'Sustainable Living',
                    'Home Security',
                    'Outdoor Living',
                ],
            ],
            [
                'name' => 'Legal',
                'slug' => 'legal',
                'subcategories' => [
                    'Business Law',
                    'Personal Injury',
                    'Family Law',
                    'Criminal Law',
                    'Immigration Law',
                    'Intellectual Property',
                    'Employment Law',
                    'Real Estate Law',
                    'Legal Advice',
                    'Law Firm Marketing',
                ],
            ],
            [
                'name' => 'Marketing & SEO',
                'slug' => 'marketing-seo',
                'subcategories' => [
                    'SEO',
                    'Content Marketing',
                    'Social Media Marketing',
                    'Email Marketing',
                    'Digital Marketing',
                    'PPC Advertising',
                    'Affiliate Marketing',
                    'Influencer Marketing',
                    'Marketing Strategy',
                    'Marketing Analytics',
                ],
            ],
            [
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'subcategories' => [
                    'Personal Development',
                    'Productivity',
                    'Relationships',
                    'Parenting',
                    'Self Improvement',
                    'Minimalism',
                    'Hobbies',
                    'Life Hacks',
                    'Motivation',
                    'Work-Life Balance',
                ],
            ],
            [
                'name' => 'News & Media',
                'slug' => 'news-media',
                'subcategories' => [
                    'World News',
                    'Politics',
                    'Business News',
                    'Technology News',
                    'Sports News',
                    'Entertainment News',
                    'Local News',
                    'Opinion & Analysis',
                    'Investigative Journalism',
                    'Media & Journalism',
                ],
            ],
            [
                'name' => 'Science',
                'slug' => 'science',
                'subcategories' => [
                    'Physics',
                    'Chemistry',
                    'Biology',
                    'Astronomy',
                    'Environmental Science',
                    'Medical Science',
                    'Psychology',
                    'Scientific Research',
                    'Science News',
                    'Science Education',
                ],
            ],
            [
                'name' => 'Art & Design',
                'slug' => 'art-design',
                'subcategories' => [
                    'Graphic Design',
                    'Web Design',
                    'Illustration',
                    'Photography',
                    'Fine Arts',
                    'Digital Art',
                    'UI/UX Design',
                    'Typography',
                    'Art History',
                    'Design Inspiration',
                ],
            ],
            [
                'name' => 'Pets & Animals',
                'slug' => 'pets-animals',
                'subcategories' => [
                    'Dog Care',
                    'Cat Care',
                    'Pet Training',
                    'Pet Health',
                    'Pet Products',
                    'Wildlife',
                    'Animal Welfare',
                    'Pet Adoption',
                    'Exotic Pets',
                    'Pet Photography',
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $subcategories = $categoryData['subcategories'];
            unset($categoryData['subcategories']);

            // Create parent category
            $parent = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name'],
                    'slug' => $categoryData['slug'],
                    'status' => 'active',
                ]
            );

            // Create subcategories
            foreach ($subcategories as $subcategoryName) {
                $subcategorySlug = Str::slug($categoryData['slug'] . '-' . $subcategoryName);
                Category::updateOrCreate(
                    ['slug' => $subcategorySlug],
                    [
                        'name' => $subcategoryName,
                        'slug' => $subcategorySlug,
                        'parent_id' => $parent->id,
                        'status' => 'active',
                    ]
                );
            }
        }

        $this->command->info('Categories and subcategories seeded successfully!');
        $this->command->info('Created ' . count($categories) . ' main categories with their subcategories.');
    }
}

