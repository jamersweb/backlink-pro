import Card from '@/Components/Shared/Card';

export default function SummaryCards({ snapshot }) {
    const cards = [];

    if (snapshot.analyzer) {
        cards.push({
            label: 'SEO Health Score',
            value: snapshot.analyzer.health_score || 0,
            color: snapshot.analyzer.health_score >= 80 ? 'text-green-600' :
                   snapshot.analyzer.health_score >= 60 ? 'text-yellow-600' : 'text-red-600',
        });
    }

    if (snapshot.google?.gsc) {
        cards.push({
            label: 'GSC Clicks (28d)',
            value: snapshot.google.gsc.clicks?.toLocaleString() || 0,
            color: 'text-blue-600',
        });
    }

    if (snapshot.google?.ga4) {
        cards.push({
            label: 'GA Sessions (28d)',
            value: snapshot.google.ga4.sessions?.toLocaleString() || 0,
            color: 'text-purple-600',
        });
    }

    if (snapshot.backlinks) {
        cards.push({
            label: 'Referring Domains',
            value: snapshot.backlinks.ref_domains?.toLocaleString() || 0,
            color: 'text-indigo-600',
        });
    }

    if (snapshot.backlinks?.delta) {
        cards.push({
            label: 'New Links',
            value: `+${snapshot.backlinks.delta.new_links || 0}`,
            color: 'text-green-600',
        });
        cards.push({
            label: 'Lost Links',
            value: `-${snapshot.backlinks.delta.lost_links || 0}`,
            color: 'text-red-600',
        });
    }

    if (cards.length === 0) {
        return null;
    }

    return (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {cards.map((card, idx) => (
                <Card key={idx}>
                    <div className="p-4">
                        <p className="text-gray-600 text-xs font-medium mb-1">{card.label}</p>
                        <p className={`text-2xl font-bold ${card.color}`}>
                            {card.value}
                        </p>
                    </div>
                </Card>
            ))}
        </div>
    );
}


