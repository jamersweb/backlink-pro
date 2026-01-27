import Card from '@/Components/Shared/Card';

export default function DeltasPanel({ delta, detailed = false }) {
    if (!delta) return null;

    return (
        <Card>
            <div className="p-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Changes Since Last Run</h3>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="text-center">
                        <p className="text-gray-600 text-xs font-medium mb-1">New Links</p>
                        <p className="text-2xl font-bold text-green-600">+{delta.new_links || 0}</p>
                    </div>
                    <div className="text-center">
                        <p className="text-gray-600 text-xs font-medium mb-1">Lost Links</p>
                        <p className="text-2xl font-bold text-red-600">-{delta.lost_links || 0}</p>
                    </div>
                    <div className="text-center">
                        <p className="text-gray-600 text-xs font-medium mb-1">New Ref Domains</p>
                        <p className="text-2xl font-bold text-green-600">+{delta.new_ref_domains || 0}</p>
                    </div>
                    <div className="text-center">
                        <p className="text-gray-600 text-xs font-medium mb-1">Lost Ref Domains</p>
                        <p className="text-2xl font-bold text-red-600">-{delta.lost_ref_domains || 0}</p>
                    </div>
                </div>
                {detailed && delta.previous_run_id && (
                    <div className="mt-4 pt-4 border-t border-gray-200">
                        <p className="text-sm text-gray-600">
                            Compared to run #{delta.previous_run_id}
                        </p>
                    </div>
                )}
            </div>
        </Card>
    );
}


