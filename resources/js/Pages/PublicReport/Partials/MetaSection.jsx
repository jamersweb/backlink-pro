import Card from '@/Components/Shared/Card';

export default function MetaSection({ data }) {
    if (!data) return null;

    return (
        <Card>
            <div className="p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Meta Tags Status</h2>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p className="text-sm text-gray-600">Draft Changes</p>
                        <p className="text-2xl font-bold text-yellow-600">{data.drafts_count || 0}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Failed Publishes</p>
                        <p className="text-2xl font-bold text-red-600">{data.failed_count || 0}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Last Published</p>
                        <p className="text-sm font-semibold text-gray-900">
                            {data.last_published_at 
                                ? new Date(data.last_published_at).toLocaleDateString()
                                : 'Never'
                            }
                        </p>
                    </div>
                </div>
            </div>
        </Card>
    );
}


