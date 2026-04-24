<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import { ref, computed, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';

// Register Chart.js components
Chart.register(...registerables);

const props = defineProps({
    stats: Object,
    lowStockDrugs: Array,
    salesData: Array, // Array of sales over time
    categoryStats: Array, // Sales by category
    recentSales: Array, // Recent sales
    stockByLocation: Object, // Stock grouped by location (pharmacy vs depots)
});

const salesChart = ref(null);
const categoryChart = ref(null);
const stockChart = ref(null);

onMounted(() => {
    initSalesChart();
    initCategoryChart();
    initStockChart();
});

const initSalesChart = () => {
    if (!salesChart.value) return;

    const hasData = props.salesData && props.salesData.length > 0 && props.salesData.some(d => d.count > 0);
    const labels = hasData ? props.salesData.map(d => d.date) : [];
    const data = hasData ? props.salesData.map(d => d.count) : [];

    const ctx = salesChart.value.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ventes',
                data: data,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
};

const initCategoryChart = () => {
    if (!categoryChart.value) return;

    const hasData = props.categoryStats && props.categoryStats.length > 0 && props.categoryStats.some(c => c.count > 0);
    const labels = hasData ? props.categoryStats.map(c => c.name) : [];
    const data = hasData ? props.categoryStats.map(c => c.count) : [];

    const ctx = categoryChart.value.getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(139, 92, 246)',
                    'rgb(236, 72, 153)',
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
};

const initStockChart = () => {
    if (!stockChart.value) return;

    const ctx = stockChart.value.getContext('2d');

    // Check if we have location-based data
    const hasLocationData = props.stockByLocation &&
        (props.stockByLocation.pharmacy?.en_stock > 0 ||
         props.stockByLocation.depots?.en_stock > 0);

    // If no location data, show empty state
    if (!hasLocationData) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
        return;
    }

    const pharmacy = props.stockByLocation?.pharmacy || { en_stock: 0, vendue: 0 };
    const depots = props.stockByLocation?.depots || { en_stock: 0, vendue: 0 };

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['En Stock', 'Vendus'],
            datasets: [
                {
                    label: pharmacy.label || 'Pharmacie',
                    data: [pharmacy.en_stock, pharmacy.vendue],
                    backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(59, 130, 246, 0.5)'],
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                },
                {
                    label: depots.label || 'Dépôts',
                    data: [depots.en_stock, depots.vendue],
                    backgroundColor: ['rgba(16, 185, 129, 0.8)', 'rgba(16, 185, 129, 0.5)'],
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    title: {
                        display: true,
                        text: 'Nombre d\'unités'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
};

const stockPercentage = computed(() => {
    if (!props.stats?.total_units) return 0;
    return Math.round((props.stats.total_stock_pharmacy / props.stats.total_units) * 100);
});

const criticalStock = computed(() => {
    return props.lowStockDrugs?.filter(d => d.current_stock === 0) || [];
});

const lowStock = computed(() => {
    return props.lowStockDrugs?.filter(d => d.current_stock > 0) || [];
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">
                        Tableau de Bord
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Vue d'ensemble de votre pharmacie
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="px-4 py-2 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm font-semibold text-blue-800">
                                {{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Main Stats Grid - Using new StatsCard component with staggered animations -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6 stagger-fast">
                    <StatsCard
                        :title="stats?.stock_scope_label || 'Stock Pharmacie'"
                        :value="stats?.total_stock_pharmacy || 0"
                        icon="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        variant="primary"
                        :progress="stockPercentage"
                        :animation-delay="0"
                        :href="route('drug-units.index')"
                    />

                    <StatsCard
                        title="Ventes Aujourd'hui"
                        :value="stats?.total_sales_today || 0"
                        icon="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                        variant="success"
                        subtitle="Unités vendues"
                        :animation-delay="50"
                        :href="route('sales.index')"
                    />

                    <StatsCard
                        title="Alertes Péremption"
                        :value="stats?.expiring_soon || 0"
                        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        variant="warning"
                        subtitle="Prochains 30 jours"
                        :animation-delay="100"
                    />

                    <StatsCard
                        title="Stock Faible"
                        :value="lowStockDrugs?.length || 0"
                        icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        variant="danger"
                        :subtitle="`${criticalStock.length} en rupture de stock`"
                        :animation-delay="150"
                    />
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Sales Trend Chart -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100">
                            <h3 class="text-lg font-bold text-blue-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                                </svg>
                                Tendance des Ventes
                            </h3>
                            <p class="text-sm text-blue-700 mt-1">7 derniers jours</p>
                        </div>
                        <div class="p-6">
                            <div class="h-64 relative">
                                <canvas v-if="salesData && salesData.some(d => d.count > 0)" ref="salesChart"></canvas>
                                <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
                                    <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <p class="text-sm font-medium">Aucune vente sur les 7 derniers jours</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Distribution -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-purple-100">
                            <h3 class="text-lg font-bold text-purple-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                                </svg>
                                Par Catégorie
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="h-64 relative">
                                <canvas v-if="categoryStats && categoryStats.some(c => c.count > 0)" ref="categoryChart"></canvas>
                                <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
                                    <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                                    </svg>
                                    <p class="text-sm font-medium">Aucune vente par catégorie</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Overview Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-indigo-100">
                        <h3 class="text-lg font-bold text-indigo-900 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Vue d'Ensemble du Stock par Emplacement
                        </h3>
                        <p class="text-sm text-indigo-700 mt-1">Comparaison Pharmacie vs Dépôts</p>
                    </div>
                    <div class="p-6">
                        <div class="h-64 relative">
                            <canvas ref="stockChart"></canvas>
                            <div v-if="!stockByLocation || (!stockByLocation.pharmacy?.en_stock && !stockByLocation.depots?.en_stock)"
                                 class="flex flex-col items-center justify-center h-full text-gray-400 absolute inset-0 bg-white/80">
                                <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <p class="text-sm font-medium">Aucune donnée de stock disponible</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-red-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-red-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Alertes Stock Faible
                            </h3>
                            <span class="px-3 py-1 bg-red-200 text-red-800 text-xs font-bold rounded-full">
                                {{ lowStockDrugs?.length || 0 }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto" v-if="lowStockDrugs && lowStockDrugs.length > 0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Médicament
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Stock Actuel
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Stock Min
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Niveau
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="drug in lowStockDrugs" :key="drug.id" class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-red-100 to-red-200 rounded-lg flex items-center justify-center mr-3">
                                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">{{ drug.name }}</div>
                                                <div class="text-xs text-gray-500">{{ drug.category?.name || '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold" :class="drug.current_stock === 0 ? 'text-red-600' : 'text-yellow-600'">
                                            {{ drug.current_stock }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-700">{{ drug.min_stock }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div 
                                                class="h-2 rounded-full transition-all duration-500"
                                                :class="drug.current_stock === 0 ? 'bg-red-600' : 'bg-yellow-500'"
                                                :style="{ width: Math.min((drug.current_stock / drug.min_stock) * 100, 100) + '%' }"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span 
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ring-1"
                                            :class="drug.current_stock === 0 
                                                ? 'bg-red-100 text-red-800 ring-red-600/20' 
                                                : 'bg-yellow-100 text-yellow-800 ring-yellow-600/20'"
                                        >
                                            {{ drug.current_stock === 0 ? 'Rupture' : 'Faible' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-8 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 font-medium">Aucun médicament en rupture ou stock faible</p>
                        <p class="text-gray-400 text-sm mt-1">Tous les stocks sont au niveau optimal</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
