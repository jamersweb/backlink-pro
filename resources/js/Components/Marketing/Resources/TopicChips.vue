<template>
    <div class="topic-chips flex flex-wrap gap-2">
        <button
            v-for="topic in topics"
            :key="topic"
            @click="toggleTopic(topic)"
            :aria-pressed="selectedTopics.includes(topic)"
            :class="[
                'px-3 py-1 rounded-full text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                selectedTopics.includes(topic)
                    ? 'bg-primary text-white'
                    : 'bg-surface border border-border text-muted hover:text-text'
            ]"
        >
            {{ topic }}
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    topics: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['update:topics']);

const selectedTopics = ref([]);

const toggleTopic = (topic) => {
    const index = selectedTopics.value.indexOf(topic);
    if (index > -1) {
        selectedTopics.value.splice(index, 1);
    } else {
        selectedTopics.value.push(topic);
    }
    emit('update:topics', selectedTopics.value);
};
</script>
