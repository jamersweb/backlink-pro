<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Marketing Leads" />
        
        <div class="max-w-7xl mx-auto py-8 px-4">
            <h1 class="text-3xl font-bold mb-8">Marketing Leads</h1>
            
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="flex gap-4">
                    <select
                        v-model="filters.type"
                        @change="applyFilters"
                        class="px-4 py-2 border rounded"
                    >
                        <option value="all">All Types</option>
                        <option value="contact">Contact Requests</option>
                        <option value="partner">Partner Applications</option>
                        <option value="free-plan">Free Plan Requests</option>
                    </select>
                    <select
                        v-model="filters.status"
                        @change="applyFilters"
                        class="px-4 py-2 border rounded"
                    >
                        <option value="all">All Statuses</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="qualified">Qualified</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b mb-6">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'px-4 py-2 border-b-2 font-semibold',
                        activeTab === tab.key ? 'border-blue-500 text-blue-600' : 'border-transparent'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Contact Requests -->
            <div v-if="activeTab === 'contacts'" class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="contact in contacts.data" :key="contact.id">
                            <td class="px-6 py-4 whitespace-nowrap">{{ contact.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ contact.email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ contact.inquiry_type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select
                                    :value="contact.status"
                                    @change="updateStatus('contact', contact.id, $event.target.value)"
                                    class="px-2 py-1 border rounded text-sm"
                                >
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(contact.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a :href="`mailto:${contact.email}`" class="text-blue-600 hover:underline">Email</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="contacts.data.length === 0" class="p-8 text-center text-gray-500">
                    No contact requests found.
                </div>
            </div>

            <!-- Partner Applications -->
            <div v-if="activeTab === 'partners'" class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="partner in partners.data" :key="partner.id">
                            <td class="px-6 py-4 whitespace-nowrap">{{ partner.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ partner.email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ partner.partner_type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select
                                    :value="partner.status"
                                    @change="updateStatus('partner', partner.id, $event.target.value)"
                                    class="px-2 py-1 border rounded text-sm"
                                >
                                    <option value="new">New</option>
                                    <option value="reviewing">Reviewing</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(partner.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a :href="`mailto:${partner.email}`" class="text-blue-600 hover:underline">Email</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="partners.data.length === 0" class="p-8 text-center text-gray-500">
                    No partner applications found.
                </div>
            </div>

            <!-- Free Plan Requests -->
            <div v-if="activeTab === 'free-plans'" class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Website</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Segment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Risk Mode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="plan in freePlans.data" :key="plan.id">
                            <td class="px-6 py-4 whitespace-nowrap">{{ plan.website }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ plan.email || 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ plan.segment }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ plan.risk_mode }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select
                                    :value="plan.status"
                                    @change="updateStatus('free-plan', plan.id, $event.target.value)"
                                    class="px-2 py-1 border rounded text-sm"
                                >
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(plan.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="freePlans.data.length === 0" class="p-8 text-center text-gray-500">
                    No free plan requests found.
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    contacts: {
        type: Object,
        required: true,
    },
    partners: {
        type: Object,
        required: true,
    },
    freePlans: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

const activeTab = ref('contacts');

const tabs = [
    { key: 'contacts', label: `Contact Requests (${props.contacts.total || 0})` },
    { key: 'partners', label: `Partner Applications (${props.partners.total || 0})` },
    { key: 'free-plans', label: `Free Plan Requests (${props.freePlans.total || 0})` },
];

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const updateStatus = (type, id, status) => {
    router.put(`/admin/marketing-leads/${type}/${id}/status`, { status }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyFilters = () => {
    router.get('/admin/marketing-leads', props.filters, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
