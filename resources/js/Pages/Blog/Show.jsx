import { Link } from '@inertiajs/react';
import Card from '../../Components/Shared/Card';

export default function BlogShow({ post, relatedPosts }) {
    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <header className="bg-white border-b border-gray-200">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <Link href="/blog" className="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                        ← Back to Blog
                    </Link>
                    {post.category && (
                        <span className="inline-block px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800 rounded mb-4">
                            {post.category.name}
                        </span>
                    )}
                    <h1 className="text-4xl font-bold text-gray-900 mt-4">{post.title}</h1>
                    <div className="flex items-center gap-4 text-sm text-gray-600 mt-4">
                        <span>{new Date(post.published_at).toLocaleDateString()}</span>
                        <span>•</span>
                        <span>{post.views || 0} views</span>
                    </div>
                </div>
            </header>

            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Main Content */}
                    <article className="lg:col-span-2">
                        <Card className="bg-white border border-gray-200 shadow-md">
                            {post.featured_image && (
                                <img
                                    src={post.featured_image}
                                    alt={post.title}
                                    className="w-full h-64 object-cover rounded-lg mb-6"
                                />
                            )}
                            {post.excerpt && (
                                <p className="text-xl text-gray-600 mb-6 font-medium">{post.excerpt}</p>
                            )}
                            <div 
                                className="prose max-w-none"
                                dangerouslySetInnerHTML={{ __html: post.content }}
                            />
                        </Card>
                    </article>

                    {/* Sidebar */}
                    <aside className="lg:col-span-1">
                        {relatedPosts && relatedPosts.length > 0 && (
                            <Card className="bg-white border border-gray-200 shadow-md">
                                <h3 className="text-lg font-bold text-gray-900 mb-4">Related Posts</h3>
                                <div className="space-y-4">
                                    {relatedPosts.map((relatedPost) => (
                                        <Link
                                            key={relatedPost.id}
                                            href={`/blog/${relatedPost.slug}`}
                                            className="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                                        >
                                            <h4 className="font-semibold text-gray-900 mb-1 line-clamp-2">
                                                {relatedPost.title}
                                            </h4>
                                            <p className="text-xs text-gray-500">
                                                {new Date(relatedPost.published_at).toLocaleDateString()}
                                            </p>
                                        </Link>
                                    ))}
                                </div>
                            </Card>
                        )}
                    </aside>
                </div>
            </div>
        </div>
    );
}


