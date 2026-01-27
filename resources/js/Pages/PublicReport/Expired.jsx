import PublicLayout from '../../Components/Layout/PublicLayout';
import Card from '../../Components/Shared/Card';

export default function PublicReportExpired({ report }) {
    return (
        <PublicLayout>
            <div className="max-w-md mx-auto">
                <Card>
                    <div className="p-6 text-center">
                        <div className="mb-4">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 className="text-xl font-bold text-gray-900 mb-2">Report Expired</h2>
                        <p className="text-gray-600">
                            This report is no longer available.
                            {report.expires_at && (
                                <span className="block mt-2 text-sm text-gray-500">
                                    Expired on: {new Date(report.expires_at).toLocaleDateString()}
                                </span>
                            )}
                        </p>
                    </div>
                </Card>
            </div>
        </PublicLayout>
    );
}


