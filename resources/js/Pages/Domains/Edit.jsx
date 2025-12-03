import { useForm, router } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function DomainsEdit({ domain }) {
    const { data, setData, put, processing, errors } = useForm({
        name: domain.name || '',
        status: domain.status || 'active',
        default_settings: domain.default_settings || {},
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/domains/${domain.id}`);
    };

    return (
        <AppLayout header="Edit Domain">
            <Card>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Input
                        label="Domain Name"
                        name="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        required
                    />

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            name="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Updating...' : 'Update Domain'}
                        </Button>
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </Card>
        </AppLayout>
    );
}

