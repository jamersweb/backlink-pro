import Select from '@/Components/Shared/Select';
import Input from '@/Components/Shared/Input';

export default function FiltersBar({ filters, uniqueTlds, onFilterChange }) {
    const handleChange = (key, value) => {
        onFilterChange({ [key]: value || null });
    };

    return (
        <div className="flex flex-wrap gap-4">
            <div className="flex-1 min-w-[200px]">
                <Input
                    type="text"
                    placeholder="Search..."
                    value={filters.search || ''}
                    onChange={(e) => handleChange('search', e.target.value)}
                />
            </div>
            <div className="w-48">
                <Select
                    value={filters.rel || ''}
                    onChange={(e) => handleChange('rel', e.target.value)}
                >
                    <option value="">All Rel Types</option>
                    <option value="follow">Follow</option>
                    <option value="nofollow">Nofollow</option>
                    <option value="ugc">UGC</option>
                    <option value="sponsored">Sponsored</option>
                </Select>
            </div>
            {uniqueTlds && uniqueTlds.length > 0 && (
                <div className="w-48">
                    <Select
                        value={filters.tld || ''}
                        onChange={(e) => handleChange('tld', e.target.value)}
                    >
                        <option value="">All TLDs</option>
                        {uniqueTlds.map((tld) => (
                            <option key={tld} value={tld}>{tld}</option>
                        ))}
                    </Select>
                </div>
            )}
        </div>
    );
}


