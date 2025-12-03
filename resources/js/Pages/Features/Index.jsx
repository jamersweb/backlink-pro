import { Link } from '@inertiajs/react';
import Button from '../../Components/Shared/Button';

export default function FeaturesIndex() {
    const features = [
        {
            category: 'Automation',
            title: 'Fully Automated Backlink Building',
            description: 'Set up campaigns once and let our AI-powered system handle the rest. No manual work required.',
            icon: '🤖',
            color: 'red',
            details: [
                'Automated account creation',
                'Smart content generation',
                'Email verification handling',
                'Link submission automation'
            ]
        },
        {
            category: 'Backlink Types',
            title: '4 Types of Backlinks',
            description: 'Support for all major backlink types to diversify your link profile.',
            icon: '🔗',
            color: 'green',
            details: [
                'Comment Backlinks',
                'Profile Backlinks',
                'Forum Backlinks',
                'Guest Posting'
            ]
        },
        {
            category: 'Analytics',
            title: 'Comprehensive Analytics',
            description: 'Track your backlink performance with detailed reports and real-time statistics.',
            icon: '📊',
            color: 'red',
            details: [
                'Real-time backlink tracking',
                'Performance charts',
                'Campaign analytics',
                'Export reports (CSV/JSON)'
            ]
        },
        {
            category: 'Gmail Integration',
            title: 'Gmail OAuth Integration',
            description: 'Securely connect your Gmail account for automated email verification.',
            icon: '📧',
            color: 'green',
            details: [
                'Secure OAuth connection',
                'Multiple account support',
                'Automatic email verification',
                'Email link clicking'
            ]
        },
        {
            category: 'Campaign Management',
            title: 'Multi-Campaign Management',
            description: 'Create and manage multiple campaigns simultaneously with ease.',
            icon: '📈',
            color: 'red',
            details: [
                'Unlimited campaigns (based on plan)',
                'Campaign scheduling',
                'Status tracking',
                'Pause/Resume functionality'
            ]
        },
        {
            category: 'Proxy Support',
            title: 'Proxy & Captcha Support',
            description: 'Built-in proxy rotation and captcha solving for maximum success rates.',
            icon: '🔐',
            color: 'green',
            details: [
                'Proxy rotation',
                '2Captcha integration',
                'AntiCaptcha support',
                'Health monitoring'
            ]
        },
        {
            category: 'Content Generation',
            title: 'AI-Powered Content',
            description: 'Generate high-quality content using advanced AI models.',
            icon: '✨',
            color: 'red',
            details: [
                'LLM integration (DeepSeek, OpenAI)',
                'Context-aware generation',
                'Multiple content types',
                'Quality optimization'
            ]
        },
        {
            category: 'Domain Management',
            title: 'Domain & Project Management',
            description: 'Organize your campaigns by domain and track performance per project.',
            icon: '🌐',
            color: 'green',
            details: [
                'Multiple domain support',
                'Domain-specific settings',
                'Performance tracking',
                'Statistics per domain'
            ]
        }
    ];

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
                            <Link href="/about" className="text-gray-700 hover:text-red-600 font-medium transition-colors">About</Link>
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
                            Powerful Features
                        </span>
                    </h1>
                    <p className="text-xl text-gray-600 leading-relaxed">
                        Everything you need to build quality backlinks, track performance, and grow your SEO—all in one platform.
                    </p>
                </div>
            </section>

            {/* Features Grid */}
            <section className="py-24 bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {features.map((feature, index) => (
                            <div key={index} className={`bg-gradient-to-br ${
                                feature.color === 'red' ? 'from-red-50 to-white border-red-200' : 'from-green-50 to-white border-green-200'
                            } border-2 rounded-2xl p-8 hover:shadow-xl transition-all duration-300`}>
                                <div className="flex items-start gap-4 mb-4">
                                    <div className={`text-4xl bg-white rounded-xl p-3 shadow-lg ${
                                        feature.color === 'red' ? 'border-2 border-red-300' : 'border-2 border-green-300'
                                    }`}>
                                        {feature.icon}
                                    </div>
                                    <div className="flex-1">
                                        <span className={`text-xs font-bold uppercase tracking-wider mb-2 block ${
                                            feature.color === 'red' ? 'text-red-600' : 'text-green-600'
                                        }`}>
                                            {feature.category}
                                        </span>
                                        <h3 className="text-2xl font-bold text-gray-900 mb-2">{feature.title}</h3>
                                        <p className="text-gray-600 mb-4">{feature.description}</p>
                                        <ul className="space-y-2">
                                            {feature.details.map((detail, idx) => (
                                                <li key={idx} className="flex items-start gap-2 text-sm text-gray-700">
                                                    <span className={`mt-1 ${
                                                        feature.color === 'red' ? 'text-red-500' : 'text-green-500'
                                                    }`}>✓</span>
                                                    <span>{detail}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Comparison Section */}
            <section className="py-24 bg-gradient-to-br from-gray-50 to-white">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 className="text-4xl font-bold text-gray-900 mb-12 text-center">Why Choose Backlink Pro?</h2>
                    <div className="space-y-6">
                        {[
                            {
                                title: '100% Automated',
                                description: 'Set it and forget it. Our platform handles everything from account creation to link verification.',
                                icon: '⚡'
                            },
                            {
                                title: 'Cost Effective',
                                description: 'Save thousands on manual link building services. Our automated platform is a fraction of the cost.',
                                icon: '💰'
                            },
                            {
                                title: 'Scalable',
                                description: 'Build hundreds of backlinks per month without increasing your workload.',
                                icon: '📈'
                            },
                            {
                                title: 'Transparent',
                                description: 'See exactly where your links are being placed with detailed reporting and verification.',
                                icon: '🔍'
                            }
                        ].map((item, index) => (
                            <div key={index} className="bg-white rounded-xl p-6 border-2 border-gray-200 flex items-start gap-4">
                                <div className="text-4xl">{item.icon}</div>
                                <div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">{item.title}</h3>
                                    <p className="text-gray-600">{item.description}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="py-24 bg-gradient-to-r from-red-600 to-green-600">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-4xl md:text-5xl font-bold text-white mb-6">
                        Ready to Get Started?
                    </h2>
                    <p className="text-xl text-red-50 mb-10 max-w-2xl mx-auto">
                        Start building quality backlinks today with our free plan. No credit card required.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <Link href="/register">
                            <Button variant="white" className="text-lg px-8 py-4">
                                Start Free Trial
                            </Button>
                        </Link>
                        <Link href="/pricing">
                            <Button variant="outline" className="text-lg px-8 py-4 border-white text-white hover:bg-white hover:text-red-600">
                                View Pricing
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


