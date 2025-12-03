import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';

export default function DocumentationIndex() {
    const sections = [
        {
            title: "Getting Started",
            items: [
                { title: "Account Setup", content: "Learn how to create your account and configure your profile." },
                { title: "Subscription Plans", content: "Understand our pricing plans and choose the right one for you." },
                { title: "First Campaign", content: "Step-by-step guide to creating your first backlink campaign." },
            ]
        },
        {
            title: "Campaign Management",
            items: [
                { title: "Creating Campaigns", content: "Detailed guide on the 7-step campaign creation process." },
                { title: "Campaign Settings", content: "Configure backlink types, limits, and content settings." },
                { title: "Managing Campaigns", content: "Edit, pause, or delete campaigns. View campaign analytics." },
            ]
        },
        {
            title: "Backlinks",
            items: [
                { title: "Viewing Backlinks", content: "How to view and filter your backlinks by status and type." },
                { title: "Backlink Status", content: "Understanding pending, submitted, verified, and error statuses." },
                { title: "Verification Process", content: "How backlinks are verified and what to do if verification fails." },
            ]
        },
        {
            title: "Account Management",
            items: [
                { title: "Gmail Integration", content: "Connect and manage Gmail accounts for email verification." },
                { title: "Domain Management", content: "Add and manage domains for your campaigns." },
                { title: "Site Accounts", content: "Manage site accounts used for backlink creation." },
            ]
        },
        {
            title: "Analytics & Reports",
            items: [
                { title: "Dashboard Overview", content: "Understanding your dashboard statistics and metrics." },
                { title: "Reports & Analytics", content: "View detailed reports on campaign performance and backlink statistics." },
                { title: "Activity Feed", content: "Track recent activity and notifications." },
            ]
        },
        {
            title: "Subscription & Billing",
            items: [
                { title: "Managing Subscription", content: "View subscription status, payment history, and manage billing." },
                { title: "Upgrading Plans", content: "How to upgrade or change your subscription plan." },
                { title: "Cancellation", content: "How to cancel your subscription if needed." },
            ]
        },
    ];

    return (
        <AppLayout header="Documentation">
            <div className="space-y-6">
                <Card>
                    <h2 className="text-2xl font-bold text-gray-900 mb-4">Welcome to Backlink Pro Documentation</h2>
                    <p className="text-gray-600 mb-6">
                        This documentation will help you get started and make the most of Backlink Pro. 
                        Find guides, tutorials, and answers to common questions.
                    </p>
                </Card>

                {sections.map((section, sectionIndex) => (
                    <Card key={sectionIndex} title={section.title}>
                        <div className="space-y-4">
                            {section.items.map((item, itemIndex) => (
                                <div key={itemIndex} className="border-l-4 border-indigo-500 pl-4">
                                    <h3 className="font-semibold text-gray-900 mb-1">{item.title}</h3>
                                    <p className="text-sm text-gray-600">{item.content}</p>
                                </div>
                            ))}
                        </div>
                    </Card>
                ))}

                {/* Quick Start Guide */}
                <Card title="Quick Start Guide">
                    <div className="space-y-4">
                        <div className="flex items-start gap-3">
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center font-semibold text-indigo-600">
                                1
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900">Create Your Account</h3>
                                <p className="text-sm text-gray-600">Sign up and choose a subscription plan that fits your needs.</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-3">
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center font-semibold text-indigo-600">
                                2
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900">Add a Domain</h3>
                                <p className="text-sm text-gray-600">Add the domain you want to build backlinks for.</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-3">
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center font-semibold text-indigo-600">
                                3
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900">Connect Gmail</h3>
                                <p className="text-sm text-gray-600">Connect a Gmail account for email verification.</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-3">
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center font-semibold text-indigo-600">
                                4
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900">Create Your First Campaign</h3>
                                <p className="text-sm text-gray-600">Use the campaign wizard to set up your first backlink campaign.</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-3">
                            <div className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center font-semibold text-indigo-600">
                                5
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900">Monitor & Optimize</h3>
                                <p className="text-sm text-gray-600">Track your backlinks, view reports, and optimize your campaigns.</p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

