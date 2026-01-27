import { Link } from '@inertiajs/react';

export default function Checklist({ items }) {
    if (!items || items.length === 0) {
        return null;
    }

    return (
        <div className="mt-3">
            <h4 className="text-xs font-semibold text-gray-700 mb-2">Checklist:</h4>
            <ol className="space-y-2">
                {items.map((step, idx) => (
                    <li key={idx} className="flex items-start gap-2 text-sm text-gray-600">
                        <span className="flex-shrink-0 w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                            {step.step || idx + 1}
                        </span>
                        <span className="flex-1">
                            {step.link ? (
                                <Link href={step.link} className="text-blue-600 hover:text-blue-800 underline">
                                    {step.text}
                                </Link>
                            ) : (
                                <span>{step.text}</span>
                            )}
                        </span>
                    </li>
                ))}
            </ol>
        </div>
    );
}


