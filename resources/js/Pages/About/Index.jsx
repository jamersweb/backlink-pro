import { Link } from '@inertiajs/react';
import Button from '../../Components/Shared/Button';

export default function AboutIndex() {
    return (
        <div className="min-h-screen bg-white">
            {/* Navigation */}
            <nav className="bg-white border-b border-gray-200 sticky top-0 z-50 backdrop-blur-sm bg-white/95">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center h-20">
                        <div className="flex items-center">
                            <Link href="/" className="text-2xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                                ⚡ Backlink Pro
                            </Link>
                        </div>
                        <div className="hidden md:flex items-center space-x-8">
                            <Link href="/" className="text-gray-700 hover:text-red-600 font-medium transition-colors">Home</Link>
                            <Link href="/features" className="text-gray-700 hover:text-red-600 font-medium transition-colors">Features</Link>
                            <Link href="/pricing" className="text-gray-700 hover:text-red-600 font-medium transition-colors">Pricing</Link>
                            <Link href="/contact" className="text-gray-700 hover:text-red-600 font-medium transition-colors">Contact</Link>
                            <Link href="/login" className="text-gray-700 hover:text-red-600 font-medium transition-colors">Login</Link>
                            <Link href="/register">
                                <Button variant="primary" className="px-6 py-2.5">Get Started</Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            {/* Hero Section */}
            <section className="bg-gradient-to-br from-red-50 via-white to-green-50 py-20">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 className="text-5xl md:text-6xl font-bold mb-6">
                        <span className="bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                            About Backlink Pro
                        </span>
                    </h1>
                    <p className="text-xl text-gray-600 leading-relaxed">
                        We're on a mission to make SEO link building accessible, automated, and effective for everyone.
                    </p>
                </div>
            </section>

            {/* Our Story */}
            <section className="py-24 bg-white">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 className="text-4xl font-bold text-gray-900 mb-8 text-center">Our Story</h2>
                    <div className="prose prose-lg max-w-none">
                        <p className="text-gray-600 leading-relaxed mb-6">
                            Backlink Pro was born from a simple frustration: building quality backlinks is time-consuming, expensive, and often requires technical expertise that many marketers don't have.
                        </p>
                        <p className="text-gray-600 leading-relaxed mb-6">
                            We set out to change that. Our platform automates the entire backlink building process—from finding opportunities to creating accounts, submitting content, and verifying links—all while maintaining quality and following best practices.
                        </p>
                        <p className="text-gray-600 leading-relaxed">
                            Today, thousands of marketers and SEO professionals trust Backlink Pro to build their link profiles, improve their search rankings, and drive organic traffic—all on autopilot.
                        </p>
                    </div>
                </div>
            </section>

            {/* Mission */}
            <section className="py-24 bg-gradient-to-br from-gray-50 to-white">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 className="text-4xl font-bold text-gray-900 mb-8 text-center">Our Mission</h2>
                    <div className="bg-white rounded-2xl p-12 shadow-xl border-2 border-green-200">
                        <p className="text-xl text-gray-700 leading-relaxed text-center">
                            To democratize SEO link building by making it accessible, affordable, and automated for businesses of all sizes. We believe that every website deserves quality backlinks, regardless of budget or technical expertise.
                        </p>
                    </div>
                </div>
            </section>

            {/* Values */}
            <section className="py-24 bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 className="text-4xl font-bold text-gray-900 mb-12 text-center">Our Values</h2>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {[
                            {
                                title: 'Innovation',
                                description: 'We continuously innovate to stay ahead of SEO trends and provide cutting-edge automation solutions.',
                                icon: '💡',
                                color: 'red'
                            },
                            {
                                title: 'Transparency',
                                description: 'We believe in honest communication, clear pricing, and transparent reporting on all backlink activities.',
                                icon: '🔍',
                                color: 'green'
                            },
                            {
                                title: 'Quality',
                                description: 'We prioritize quality over quantity, ensuring every backlink meets high standards and provides real value.',
                                icon: '⭐',
                                color: 'red'
                            }
                        ].map((value, index) => (
                            <div key={index} className={`bg-gradient-to-br ${
                                value.color === 'red' ? 'from-red-50 to-white border-red-200' : 'from-green-50 to-white border-green-200'
                            } border-2 rounded-2xl p-8 hover:shadow-xl transition-all duration-300`}>
                                <div className="text-5xl mb-4">{value.icon}</div>
                                <h3 className="text-2xl font-bold text-gray-900 mb-3">{value.title}</h3>
                                <p className="text-gray-600 leading-relaxed">{value.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Team Section */}
            <section className="py-24 bg-gradient-to-br from-red-50 via-white to-green-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 className="text-4xl font-bold text-gray-900 mb-12 text-center">The Team</h2>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {[
                            {
                                name: 'John Doe',
                                role: 'Founder & CEO',
                                bio: '10+ years in SEO and digital marketing. Previously led SEO at multiple tech companies.',
                                avatar: '👨‍💼'
                            },
                            {
                                name: 'Jane Smith',
                                role: 'CTO',
                                bio: 'Full-stack developer with expertise in automation and AI. Passionate about making complex things simple.',
                                avatar: '👩‍💻'
                            },
                            {
                                name: 'Mike Johnson',
                                role: 'Head of Product',
                                bio: 'Product strategist focused on user experience and growth. Believes in building products people love.',
                                avatar: '👨‍💼'
                            }
                        ].map((member, index) => (
                            <div key={index} className="bg-white rounded-2xl p-8 shadow-lg border-2 border-gray-200 text-center">
                                <div className="text-6xl mb-4">{member.avatar}</div>
                                <h3 className="text-xl font-bold text-gray-900 mb-1">{member.name}</h3>
                                <p className="text-red-600 font-semibold mb-4">{member.role}</p>
                                <p className="text-gray-600 text-sm leading-relaxed">{member.bio}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="py-24 bg-gradient-to-r from-red-600 to-green-600">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-4xl md:text-5xl font-bold text-white mb-6">
                        Join Us on Our Mission
                    </h2>
                    <p className="text-xl text-red-50 mb-10 max-w-2xl mx-auto">
                        Start building quality backlinks today and see the difference automation makes.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <Link href="/register">
                            <Button variant="white" className="text-lg px-8 py-4">
                                Get Started Free
                            </Button>
                        </Link>
                        <Link href="/contact">
                            <Button variant="outline" className="text-lg px-8 py-4 border-white text-white hover:bg-white hover:text-red-600">
                                Contact Us
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="bg-gray-900 text-gray-400 py-16">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                        <div>
                            <h3 className="text-white font-bold text-xl mb-4 bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                                Backlink Pro
                            </h3>
                            <p className="text-sm">Automated backlink building platform for SEO professionals.</p>
                        </div>
                        <div>
                            <h4 className="text-white font-semibold mb-4">Product</h4>
                            <ul className="space-y-2 text-sm">
                                <li><Link href="/pricing" className="hover:text-green-400 transition-colors">Pricing</Link></li>
                                <li><Link href="/features" className="hover:text-green-400 transition-colors">Features</Link></li>
                                <li><Link href="/documentation" className="hover:text-green-400 transition-colors">Documentation</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="text-white font-semibold mb-4">Company</h4>
                            <ul className="space-y-2 text-sm">
                                <li><Link href="/about" className="hover:text-green-400 transition-colors">About</Link></li>
                                <li><Link href="/contact" className="hover:text-green-400 transition-colors">Contact</Link></li>
                                <li><Link href="/help" className="hover:text-green-400 transition-colors">Help Center</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="text-white font-semibold mb-4">Account</h4>
                            <ul className="space-y-2 text-sm">
                                <li><Link href="/login" className="hover:text-green-400 transition-colors">Login</Link></li>
                                <li><Link href="/register" className="hover:text-green-400 transition-colors">Sign Up</Link></li>
                            </ul>
                        </div>
                    </div>
                    <div className="pt-8 border-t border-gray-800 text-center text-sm">
                        <p>&copy; {new Date().getFullYear()} Backlink Pro. Made with <span className="text-red-500">❤️</span> for marketers.</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}


