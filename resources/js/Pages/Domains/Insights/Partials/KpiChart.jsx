import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

export default function KpiChart({ data }) {
    if (!data || data.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                No KPI data available yet
            </div>
        );
    }

    const chartData = data.map(item => ({
        date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        health_score: item.seo_health_score || 0,
        sessions: item.ga_sessions_28d || 0,
    }));

    return (
        <ResponsiveContainer width="100%" height={300}>
            <LineChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis yAxisId="left" />
                <YAxis yAxisId="right" orientation="right" />
                <Tooltip />
                <Legend />
                <Line
                    yAxisId="left"
                    type="monotone"
                    dataKey="health_score"
                    stroke="#3b82f6"
                    strokeWidth={2}
                    name="Health Score"
                />
                <Line
                    yAxisId="right"
                    type="monotone"
                    dataKey="sessions"
                    stroke="#10b981"
                    strokeWidth={2}
                    name="Sessions (28d)"
                />
            </LineChart>
        </ResponsiveContainer>
    );
}


