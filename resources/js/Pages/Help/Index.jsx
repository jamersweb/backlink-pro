import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';

export default function HelpIndex() {
    const faqs = [
        {
            question: "How do I create a campaign?",
            answer: "Go to Campaigns → Create Campaign and follow the 7-step wizard. You'll need to provide campaign details, keywords, backlink types, and Gmail account for verification."
        },
        {
            question: "What types of backlinks are supported?",
            answer: "We support comment backlinks, profile backlinks, forum backlinks, and guest posting backlinks. You can select multiple types per campaign."
        },
        {
            question: "How do I connect my Gmail account?",
            answer: "Go to Gmail → Connect Gmail Account. You'll be redirected to Google OAuth to authorize access. This is needed for email verification."
        },
        {
            question: "How do I view my backlinks?",
            answer: "Go to your Campaign → View Backlinks. You can filter by status, type, and search by URL or keyword."
        },
        {
            question: "What happens if a backlink fails?",
            answer: "Failed backlinks are marked with an error status. You can view the error message and retry or adjust your campaign settings."
        },
        {
            question: "How do I manage my subscription?",
            answer: "Go to Dashboard and click 'Manage Subscription' or visit Settings → Subscription. You can view your plan, payment history, and cancel if needed."
        },
    ];

    return (
        <AppLayout header="Help & Support">
            <div className="space-y-6">
                {/* Quick Links */}
                <Card title="Quick Links">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="/documentation" className="p-4 border rounded-lg hover:bg-gray-50">
                            <h3 className="font-semibold text-gray-900 mb-2">📚 Documentation</h3>
                            <p className="text-sm text-gray-600">Complete guides and tutorials</p>
                        </a>
                        <a href="/campaign/create" className="p-4 border rounded-lg hover:bg-gray-50">
                            <h3 className="font-semibold text-gray-900 mb-2">🚀 Create Campaign</h3>
                            <p className="text-sm text-gray-600">Start building backlinks</p>
                        </a>
                        <a href="/reports" className="p-4 border rounded-lg hover:bg-gray-50">
                            <h3 className="font-semibold text-gray-900 mb-2">📊 View Reports</h3>
                            <p className="text-sm text-gray-600">Check your analytics</p>
                        </a>
                    </div>
                </Card>

                {/* FAQ Section */}
                <Card title="Frequently Asked Questions">
                    <div className="space-y-6">
                        {faqs.map((faq, index) => (
                            <div key={index} className="border-b border-gray-200 pb-4 last:border-0">
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                    {faq.question}
                                </h3>
                                <p className="text-gray-600">
                                    {faq.answer}
                                </p>
                            </div>
                        ))}
                    </div>
                </Card>

                {/* Contact Section */}
                <Card title="Need More Help?">
                    <div className="space-y-4">
                        <p className="text-gray-600">
                            If you can't find what you're looking for, we're here to help!
                        </p>
                        <div className="space-y-2">
                            <p><strong>Email:</strong> support@backlinkpro.com</p>
                            <p><strong>Documentation:</strong> <a href="/documentation" className="text-indigo-600 hover:underline">View Full Documentation</a></p>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

