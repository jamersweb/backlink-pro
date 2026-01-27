import { router } from '@inertiajs/react';
import { useState } from 'react';
import PublicLayout from '../../Components/Layout/PublicLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function PublicReportUnlock({ token }) {
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');

    const handleSubmit = (e) => {
        e.preventDefault();
        setError('');

        router.post(`/r/${token}/unlock`, { password }, {
            onError: (errors) => {
                setError(errors.password || 'Incorrect password');
            },
        });
    };

    return (
        <PublicLayout>
            <div className="max-w-md mx-auto">
                <Card>
                    <div className="p-6">
                        <h2 className="text-xl font-bold text-gray-900 mb-4">Password Required</h2>
                        <p className="text-sm text-gray-600 mb-6">
                            This report is password protected. Please enter the password to continue.
                        </p>

                        <form onSubmit={handleSubmit}>
                            <div className="mb-4">
                                <Input
                                    type="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="Enter password"
                                    required
                                    autoFocus
                                />
                                {error && (
                                    <p className="mt-2 text-sm text-red-600">{error}</p>
                                )}
                            </div>

                            <Button variant="primary" type="submit" className="w-full">
                                Unlock Report
                            </Button>
                        </form>
                    </div>
                </Card>
            </div>
        </PublicLayout>
    );
}


