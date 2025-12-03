import { useState } from 'react';
import { Link, useForm, usePage, Head } from '@inertiajs/react';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function ContactIndex() {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors, wasSuccessful } = useForm({
        name: '',
        email: '',
        subject: '',
        message: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/contact');
    };

    return (
        <>
            <Head title="Contact Us - Backlink Pro" />
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
                                <Link href="/features" className="text-gray-700 hover:text-red-600 font-medium transition-colors">Features</Link>
                                <Link href="/pricing" className="text-gray-700 hover:text-red-600 font-medium transition-colors">Pricing</Link>
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
                                Get in Touch
                            </span>
                        </h1>
                        <p className="text-xl text-gray-600 leading-relaxed">
                            Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
                        </p>
                    </div>
                </section>

                {/* Contact Form Section */}
                <section className="py-24 bg-white">
                    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
                            {/* Contact Information */}
                            <div>
                                <h2 className="text-3xl font-bold text-gray-900 mb-6">Contact Information</h2>
                                <div className="space-y-6">
                                    <div className="flex items-start gap-4">
                                        <div className="bg-red-100 rounded-lg p-3">
                                            <svg className="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-900 mb-1">Email</h3>
                                            <p className="text-gray-600">support@backlinkpro.com</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-4">
                                        <div className="bg-green-100 rounded-lg p-3">
                                            <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-900 mb-1">Response Time</h3>
                                            <p className="text-gray-600">We typically respond within 24 hours</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-4">
                                        <div className="bg-blue-100 rounded-lg p-3">
                                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-900 mb-1">Support</h3>
                                            <p className="text-gray-600">Check out our <Link href="/help" className="text-red-600 hover:text-red-700">Help Center</Link> for instant answers</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Contact Form */}
                            <div className="bg-white rounded-2xl shadow-xl border-2 border-gray-200 p-8">
                                {wasSuccessful ? (
                                    <div className="text-center py-8">
                                        <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                                            <svg className="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <h3 className="text-xl font-bold text-gray-900 mb-2">Message Sent!</h3>
                                        <p className="text-gray-600 mb-6">
                                            Thank you for contacting us. We'll get back to you as soon as possible.
                                        </p>
                                        <Button variant="primary" onClick={() => window.location.reload()}>
                                            Send Another Message
                                        </Button>
                                    </div>
                                ) : (
                                    <form onSubmit={submit} className="space-y-6">
                                        <Input
                                            label="Your Name"
                                            name="name"
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            error={errors.name}
                                            required
                                            placeholder="John Doe"
                                        />

                                        <Input
                                            label="Email Address"
                                            name="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            error={errors.email}
                                            required
                                            placeholder="john@example.com"
                                        />

                                        <Input
                                            label="Subject"
                                            name="subject"
                                            type="text"
                                            value={data.subject}
                                            onChange={(e) => setData('subject', e.target.value)}
                                            error={errors.subject}
                                            placeholder="How can we help?"
                                        />

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Message *
                                            </label>
                                            <textarea
                                                name="message"
                                                value={data.message}
                                                onChange={(e) => setData('message', e.target.value)}
                                                rows="6"
                                                required
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                                placeholder="Tell us about your question or inquiry..."
                                            />
                                            {errors.message && (
                                                <p className="mt-1 text-sm text-red-600">{errors.message}</p>
                                            )}
                                        </div>

                                        <Button
                                            type="submit"
                                            variant="primary"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            {processing ? 'Sending...' : 'Send Message'}
                                        </Button>
                                    </form>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {/* FAQ Section */}
                <section className="py-24 bg-gradient-to-br from-gray-50 to-white">
                    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                        <h2 className="text-4xl font-bold text-gray-900 mb-12 text-center">Frequently Asked Questions</h2>
                        <div className="space-y-4">
                            {[
                                {
                                    question: 'How quickly will I receive a response?',
                                    answer: 'We typically respond to all inquiries within 24 hours during business days.'
                                },
                                {
                                    question: 'Do you offer custom plans for enterprise?',
                                    answer: 'Yes! Contact us to discuss custom enterprise plans tailored to your needs.'
                                },
                                {
                                    question: 'Can I schedule a demo?',
                                    answer: 'Absolutely! Mention in your message that you\'d like a demo and we\'ll set one up.'
                                },
                                {
                                    question: 'Do you provide technical support?',
                                    answer: 'Yes, we offer comprehensive technical support to all our users. Check our Help Center for instant answers or contact us for personalized assistance.'
                                }
                            ].map((faq, index) => (
                                <div key={index} className="bg-white rounded-xl p-6 border-2 border-gray-200">
                                    <h3 className="text-lg font-bold text-gray-900 mb-2">{faq.question}</h3>
                                    <p className="text-gray-600">{faq.answer}</p>
                                </div>
                            ))}
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
        </>
    );
}


