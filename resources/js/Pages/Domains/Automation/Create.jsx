import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';

export default function AutomationCreate({ domain }) {
    const [formData, setFormData] = useState({
        name: '',
        allowed_actions: ['comment', 'profile'],
        max_retries: 2,
        headless: true,
        use_proxy: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post(`/domains/${domain.id}/automation`, formData);
    };

    const toggleAction = (action) => {
        setFormData(prev => ({
            ...prev,
            allowed_actions: prev.allowed_actions.includes(action)
                ? prev.allowed_actions.filter(a => a !== action)
                : [...prev.allowed_actions, action]
        }));
    };

    return (
        <AppLayout header="Create Automation Campaign">
            <div className="space-y-6">
                <Card>
                    <form onSubmit={handleSubmit} className="p-6 space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Campaign Name
                            </label>
                            <Input
                                type="text"
                                value={formData.name}
                                onChange={(e) => setFormData({...formData, name: e.target.value})}
                                required
                                placeholder="My Backlink Campaign"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Allowed Actions
                            </label>
                            <div className="space-y-2">
                                {['comment', 'profile', 'forum', 'guest'].map(action => (
                                    <label key={action} className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.allowed_actions.includes(action)}
                                            onChange={() => toggleAction(action)}
                                            className="mr-2"
                                        />
                                        <span className="text-sm text-gray-700 capitalize">{action}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Max Retries
                            </label>
                            <Input
                                type="number"
                                value={formData.max_retries}
                                onChange={(e) => setFormData({...formData, max_retries: parseInt(e.target.value)})}
                                min="0"
                                max="5"
                            />
                        </div>

                        <div className="space-y-2">
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={formData.headless}
                                    onChange={(e) => setFormData({...formData, headless: e.target.checked})}
                                    className="mr-2"
                                />
                                <span className="text-sm text-gray-700">Run in headless mode</span>
                            </label>
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={formData.use_proxy}
                                    onChange={(e) => setFormData({...formData, use_proxy: e.target.checked})}
                                    className="mr-2"
                                />
                                <span className="text-sm text-gray-700">Use proxy</span>
                            </label>
                        </div>

                        <div className="flex gap-3">
                            <Button type="submit" variant="primary">Create Campaign</Button>
                            <Button type="button" variant="outline" onClick={() => router.visit(`/domains/${domain.id}/automation`)}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}


