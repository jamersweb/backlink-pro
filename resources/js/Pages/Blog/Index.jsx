import { useState } from 'react';
import { router, Link } from '@inertiajs/react';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function BlogIndex({ posts, categories, filters }) {
    const [localFilters, setLocalFilters] = useState(filters || {
        category: '',
        search: '',
    });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get('/blog', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <header className="bg-white border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <h1 className="text-4xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                        Blog
                    </h1>
                    <p className="text-gray-600 mt-2">Latest insights, tips, and updates</p>
                </div>
            </header>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    {/* Sidebar */}
                    <aside className="lg:col-span-1">
                        <Card className="bg-white border border-gray-200 shadow-md">
                            <h3 className="text-lg font-bold text-gray-900 mb-4">Categories</h3>
                            <div className="space-y-2">
                                <button
                                    onClick={() => handleFilterChange('category', '')}
                                    className={`w-full text-left px-3 py-2 rounded-md transition-colors ${
                                        !localFilters.category
                                            ? 'bg-blue-100 text-blue-800 font-medium'
                                            : 'text-gray-700 hover:bg-gray-100'
                                    }`}
                                >
                                    All Posts ({posts?.total || 0})
                                </button>
                                {categories?.map((category) => (
                                    <button
                                        key={category.id}
                                        onClick={() => handleFilterChange('category', category.slug)}
                                        className={`w-full text-left px-3 py-2 rounded-md transition-colors ${
                                            localFilters.category === category.slug
                                                ? 'bg-blue-100 text-blue-800 font-medium'
                                                : 'text-gray-700 hover:bg-gray-100'
                                        }`}
                                    >
                                        {category.name} ({category.published_posts_count || 0})
                                    </button>
                                ))}
                            </div>
                        </Card>
                    </aside>

                    {/* Main Content */}
                    <main className="lg:col-span-3">
                        {/* Search */}
                        <div className="mb-6">
                            <Input
                                type="text"
                                placeholder="Search posts..."
                                value={localFilters.search || ''}
                                onChange={(e) => handleFilterChange('search', e.target.value)}
                            />
                        </div>

                        {/* Posts Grid */}
                        {posts?.data && posts.data.length > 0 ? (
                            <div className="space-y-6">
                                {posts.data.map((post) => (
                                    <Card key={post.id} className="bg-white border border-gray-200 shadow-md hover:shadow-lg transition-shadow">
                                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            {post.featured_image && (
                                                <div className="md:col-span-1">
                                                    <img
                                                        src={post.featured_image}
                                                        alt={post.title}
                                                        className="w-full h-32 object-cover rounded-lg"
                                                    />
                                                </div>
                                            )}
                                            <div className={post.featured_image ? 'md:col-span-3' : 'md:col-span-4'}>
                                                {post.category && (
                                                    <span className="inline-block px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded mb-2">
                                                        {post.category.name}
                                                    </span>
                                                )}
                                                <Link href={`/blog/${post.slug}`}>
                                                    <h2 className="text-xl font-bold text-gray-900 hover:text-blue-600 transition-colors mb-2">
                                                        {post.title}
                                                    </h2>
                                                </Link>
                                                {post.excerpt && (
                                                    <p className="text-gray-600 mb-3 line-clamp-2">{post.excerpt}</p>
                                                )}
                                                <div className="flex items-center justify-between text-sm text-gray-500">
                                                    <span>{new Date(post.published_at).toLocaleDateString()}</span>
                                                    <span>{post.views || 0} views</span>
                                                </div>
                                            </div>
                                        </div>
                                    </Card>
                                ))}
                            </div>
                        ) : (
                            <Card className="bg-white border border-gray-200 shadow-md">
                                <div className="text-center py-12">
                                    <p className="text-gray-500">No blog posts found.</p>
                                </div>
                            </Card>
                        )}

                        {/* Pagination */}
                        {posts?.links && posts.links.length > 3 && (
                            <div className="mt-6 pt-4 border-t border-gray-200">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-600">
                                        Showing {posts.from} to {posts.to} of {posts.total} results
                                    </div>
                                    <div className="flex gap-2">
                                        {posts.links.map((link, index) => (
                                            <button
                                                key={index}
                                                onClick={() => link.url && router.get(link.url)}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`px-3 py-1 text-sm rounded-md ${
                                                    link.active 
                                                        ? 'bg-blue-500 text-white' 
                                                        : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                } ${!link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}
                                                disabled={!link.url}
                                            />
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}
                    </main>
                </div>
            </div>
        </div>
    );
}


