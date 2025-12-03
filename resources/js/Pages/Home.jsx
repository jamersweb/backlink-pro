import { Link } from '@inertiajs/react';
import Button from '../Components/Shared/Button';

export default function Home() {
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
                            <Link href="/blog" className="text-gray-700 hover:text-red-600 font-medium transition-colors">
                                Blog
                            </Link>
                            <Link href="/pricing" className="text-gray-700 hover:text-red-600 font-medium transition-colors">
                                Pricing
                            </Link>
                            <Link href="/help" className="text-gray-700 hover:text-red-600 font-medium transition-colors">
                                Help
                            </Link>
                            <Link href="/login" className="text-gray-700 hover:text-red-600 font-medium transition-colors">
                                Login
                            </Link>
                            <Link href="/register">
                                <Button variant="primary" className="px-6 py-2.5">
                                    Get Started
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            {/* Hero Section */}
            <section className="relative overflow-hidden bg-gradient-to-br from-red-50 via-white to-green-50 pt-20 pb-32">
                {/* Decorative Background Elements */}
                <div className="absolute inset-0 overflow-hidden">
                    <div className="absolute top-0 left-1/4 w-96 h-96 bg-red-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
                    <div className="absolute top-0 right-1/4 w-96 h-96 bg-green-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
                    <div className="absolute -bottom-8 left-1/2 w-96 h-96 bg-yellow-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                        {/* Left Column - Text Content */}
                        <div className="text-center lg:text-left">
                            <div className="inline-block mb-6">
                                <span className="bg-gradient-to-r from-red-600 to-green-600 text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg animate-pulse">
                                    🚀 On Auto-Pilot
                                </span>
                            </div>
                            <h1 className="text-5xl md:text-6xl lg:text-7xl font-bold mb-6 leading-tight">
                                <span className="bg-gradient-to-r from-red-600 via-red-500 to-green-600 bg-clip-text text-transparent">
                                    Drive More Organic Traffic
                                </span>
                                <br />
                                <span className="text-gray-900">to Your Website</span>
                            </h1>
                            <p className="text-xl md:text-2xl text-gray-600 mb-10 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                                Automate your backlink building process. Build quality links, track performance, and grow your SEO effortlessly.
                            </p>
                            <div className="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start items-center">
                                <Link href="/register">
                                    <Button variant="primary" className="text-lg px-8 py-4 shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all">
                                        Start for Free
                                    </Button>
                                </Link>
                                <p className="text-sm text-gray-500">No credit card required</p>
                            </div>
                        </div>

                        {/* Right Column - Hero Image/Video */}
                        <div className="relative">
                            <div className="relative rounded-2xl overflow-hidden shadow-2xl transform hover:scale-105 transition-transform duration-300">
                                {/* Video Background Option */}
                                <div className="relative w-full h-[500px] bg-gradient-to-br from-red-500 via-purple-500 to-green-500 rounded-2xl overflow-hidden">
                                    {/* Placeholder for video - you can replace this with an actual video element */}
                                    <video 
                                        className="w-full h-full object-cover opacity-90"
                                        autoPlay 
                                        loop 
                                        muted 
                                        playsInline
                                        poster="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 600'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23ef4444;stop-opacity:1' /%3E%3Cstop offset='50%25' style='stop-color:%23a855f7;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%2316a34a;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='1200' height='600' fill='url(%23grad)'/%3E%3C/svg%3E"
                                    >
                                        {/* Add your video source here when available */}
                                        {/* <source src="/videos/hero-video.mp4" type="video/mp4" /> */}
                                    </video>
                                    
                                    {/* Fallback Image/Illustration */}
                                    <div className="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-red-500/90 via-purple-500/90 to-green-500/90">
                                        <div className="text-center p-8">
                                            <div className="mb-6">
                                                <div className="inline-block p-8 bg-white/20 backdrop-blur-lg rounded-3xl border-4 border-white/30 shadow-2xl">
                                                    <svg className="w-32 h-32 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <h3 className="text-3xl font-bold text-white mb-2">Automated SEO</h3>
                                            <p className="text-white/90 text-lg">Build Backlinks Faster</p>
                                        </div>
                                    </div>

                                    {/* Decorative Elements */}
                                    <div className="absolute top-4 right-4 w-20 h-20 bg-white/20 rounded-full backdrop-blur-sm border-2 border-white/30"></div>
                                    <div className="absolute bottom-4 left-4 w-16 h-16 bg-white/20 rounded-full backdrop-blur-sm border-2 border-white/30"></div>
                                </div>

                                {/* Floating Stats Cards */}
                                <div className="absolute -bottom-6 left-1/2 transform -translate-x-1/2 flex gap-4">
                                    <div className="bg-white rounded-xl p-4 shadow-xl border-2 border-green-200 transform hover:scale-110 transition-transform">
                                        <div className="text-2xl font-bold text-green-600">350+</div>
                                        <div className="text-xs text-gray-600">Links/Month</div>
                                    </div>
                                    <div className="bg-white rounded-xl p-4 shadow-xl border-2 border-red-200 transform hover:scale-110 transition-transform">
                                        <div className="text-2xl font-bold text-red-600">95%</div>
                                        <div className="text-xs text-gray-600">Success Rate</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Scroll Indicator */}
                <div className="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
                    <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </div>
            </section>

            {/* Trust Badges */}
            <section className="py-12 bg-white border-y border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <p className="text-center text-sm text-gray-500 mb-8 font-semibold uppercase tracking-wider">
                        Trusted by thousands of leading marketing teams
                    </p>
                    <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8 items-center opacity-60">
                        {['Lucid', 'ConvertKit', 'Cloudbeds', 'Planet', 'Hubstaff', 'Visme'].map((brand) => (
                            <div key={brand} className="text-center text-gray-400 font-bold text-lg">
                                {brand}
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Main Value Proposition */}
            <section className="py-24 bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                            The most loved <span className="bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">link-building platform</span> on the market
                        </h2>
                        <p className="text-xl text-gray-600 max-w-3xl mx-auto">
                            Streamline your outreach process in 4 easy steps.
                        </p>
                    </div>

                    {/* 4 Steps */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-20">
                        {[
                            {
                                step: '1',
                                title: 'Pick a Template',
                                description: 'Browse through our library of plug-and-play templates.',
                                icon: '📋',
                                color: 'red'
                            },
                            {
                                step: '2',
                                title: 'Find Outreach Opportunities',
                                description: 'Search via Google, SEO tools, or import your own CSV.',
                                icon: '🔍',
                                color: 'green'
                            },
                            {
                                step: '3',
                                title: 'Get Verified Email Addresses',
                                description: 'Find the right contacts based on their job title and seniority.',
                                icon: '✉️',
                                color: 'red'
                            },
                            {
                                step: '4',
                                title: 'Personalize with AI',
                                description: 'Get more replies by adding personalized touch using AI.',
                                icon: '🤖',
                                color: 'green'
                            }
                        ].map((item, index) => (
                            <div key={index} className="relative">
                                <div className={`bg-gradient-to-br ${
                                    item.color === 'red' ? 'from-red-50 to-red-100' : 'from-green-50 to-green-100'
                                } rounded-2xl p-8 h-full border-2 ${
                                    item.color === 'red' ? 'border-red-200' : 'border-green-200'
                                } hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2`}>
                                    <div className={`text-4xl mb-4 inline-block bg-white rounded-full w-16 h-16 flex items-center justify-center shadow-lg ${
                                        item.color === 'red' ? 'border-4 border-red-500' : 'border-4 border-green-500'
                                    }`}>
                                        {item.icon}
                                    </div>
                                    <div className={`text-sm font-bold mb-2 ${
                                        item.color === 'red' ? 'text-red-600' : 'text-green-600'
                                    }`}>
                                        Step {item.step}
                                    </div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-3">{item.title}</h3>
                                    <p className="text-gray-600 leading-relaxed">{item.description}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Use Cases Section */}
            <section className="py-24 bg-gradient-to-br from-gray-50 to-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                            What can you do <span className="bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">with Backlink Pro?</span>
                        </h2>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {[
                            {
                                title: 'Link Building',
                                description: 'Build quality backlinks to your website. Earn backlinks from relevant publications and take your organic traffic to new heights.',
                                icon: '🔗',
                                color: 'red'
                            },
                            {
                                title: 'Campaign Management',
                                description: 'Create and manage multiple campaigns with ease. Track performance and optimize your strategy automatically.',
                                icon: '📊',
                                color: 'green'
                            },
                            {
                                title: 'Analytics & Reports',
                                description: 'Get detailed insights into your backlink performance with comprehensive analytics and real-time tracking.',
                                icon: '📈',
                                color: 'red'
                            },
                            {
                                title: 'Automated Outreach',
                                description: 'Automate your email outreach process. Find contacts, personalize messages, and track responses all in one place.',
                                icon: '✉️',
                                color: 'green'
                            }
                        ].map((useCase, index) => (
                            <div key={index} className={`bg-white rounded-2xl p-8 border-2 ${
                                useCase.color === 'red' ? 'border-red-200 hover:border-red-400' : 'border-green-200 hover:border-green-400'
                            } hover:shadow-xl transition-all duration-300`}>
                                <div className={`text-5xl mb-4 inline-block bg-gradient-to-br ${
                                    useCase.color === 'red' ? 'from-red-500 to-red-600' : 'from-green-500 to-green-600'
                                } text-white rounded-xl w-16 h-16 flex items-center justify-center shadow-lg`}>
                                    {useCase.icon}
                                </div>
                                <h3 className="text-2xl font-bold text-gray-900 mb-3">{useCase.title}</h3>
                                <p className="text-gray-600 leading-relaxed">{useCase.description}</p>
                                <Link href="/register" className={`inline-block mt-4 text-sm font-semibold ${
                                    useCase.color === 'red' ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'
                                }`}>
                                    Learn more →
                                </Link>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Templates Section */}
            <section className="py-24 bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                            Plug-and-play <span className="bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">Templates</span>
                        </h2>
                        <p className="text-xl text-gray-600 max-w-3xl mx-auto">
                            Step-by-step outreach templates to kickstart your campaign in minutes.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {[
                            { name: 'Link Insertion', desc: 'Find non-competing articles relevant to your content', color: 'red' },
                            { name: 'Skyscraper Technique', desc: 'Find backlinks to top competing search results', color: 'green' },
                            { name: 'Product Alternatives', desc: 'Get mentioned as competitor alternatives', color: 'red' },
                            { name: 'Product Review', desc: 'Get reviews for your product', color: 'green' },
                            { name: 'Competitor Mentions', desc: 'Reach out to blogs mentioning competitors', color: 'red' },
                            { name: 'News Outreach', desc: 'Find journalists covering relevant topics', color: 'green' }
                        ].map((template, index) => (
                            <div key={index} className={`bg-gradient-to-br ${
                                template.color === 'red' ? 'from-red-50 to-white border-red-200' : 'from-green-50 to-white border-green-200'
                            } border-2 rounded-xl p-6 hover:shadow-lg transition-all duration-300`}>
                                <h4 className={`text-lg font-bold mb-2 ${
                                    template.color === 'red' ? 'text-red-700' : 'text-green-700'
                                }`}>
                                    {template.name}
                                </h4>
                                <p className="text-gray-600 text-sm">{template.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Testimonial */}
            <section className="py-24 bg-gradient-to-br from-red-50 via-white to-green-50">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div className="bg-white rounded-2xl p-12 shadow-2xl border-2 border-green-200">
                        <div className="flex items-center justify-center mb-6">
                            <div className="w-16 h-16 rounded-full bg-gradient-to-br from-red-500 to-green-500 flex items-center justify-center text-white text-2xl font-bold">
                                JD
                            </div>
                        </div>
                        <p className="text-xl text-gray-700 mb-6 italic leading-relaxed">
                            "With Backlink Pro, we earn 350 links from high-authority websites each month. The automation saves us countless hours and the results speak for themselves."
                        </p>
                        <div>
                            <p className="font-bold text-gray-900">John Doe</p>
                            <p className="text-gray-600 text-sm">Digital Marketing Manager at TechCorp</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="py-24 bg-gradient-to-r from-red-600 to-green-600">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-4xl md:text-5xl font-bold text-white mb-6">
                        Get started with Backlink Pro
                    </h2>
                    <p className="text-xl text-red-50 mb-10 max-w-2xl mx-auto">
                        Earn quality backlinks, recruit affiliates, and automate your outreach process.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <Link href="/register">
                            <Button variant="white" className="text-lg px-8 py-4">
                                Start for Free
                            </Button>
                        </Link>
                        <p className="text-white text-sm">No credit card required</p>
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
                            </ul>
                        </div>
                        <div>
                            <h4 className="text-white font-semibold mb-4">Support</h4>
                            <ul className="space-y-2 text-sm">
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
