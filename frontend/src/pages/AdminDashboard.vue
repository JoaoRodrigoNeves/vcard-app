<script setup>
import { inject, onMounted, ref } from 'vue';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Chart from 'primevue/chart';
import LoadingSpinner from '@/components/global/LoadingSpinner.vue'

const axios = inject('axios')
const isLoading = ref(false)
const statistics = ref([])
const dailyChart = ref([])
const weeklyChart = ref([])
const monthlyChart = ref([])
const chartOptions = ref([])
const pieOptions = ref([])
const pieData = ref([])

const formatter = new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
    useGrouping: true,
});

const loadStatistics = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('admins/statistics')
        statistics.value = response.data.statistics;
        isLoading.value = false;

    } catch (error) {
        isLoading.value = false;
        console.log(error)
    }
}

const setDailyChartData = () => {
    return {
        labels: ["Hoje"],
        datasets: [
            {
                label: 'Gastos',
                data: [statistics.value.money_spent_today],
                fill: false
            },
            {
                label: 'Ganhos',
                data: [statistics.value.money_received_today],
                fill: false,

            }
        ],
    }
}

const setWeeklyChartData = () => {
    return {
        labels: ["Semana atual"],
        datasets: [
            {
                label: 'Gastos',
                data: [statistics.value.money_spent_weekly],
                fill: false
            },
            {
                label: 'Ganhos',
                data: [statistics.value.money_received_weekly],
                fill: false,
            }
        ],
    }
}

const setMonthlyChartData = () => {
    return {
        labels: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"],
        datasets: [
            {
                label: 'Gastos',
                data: Object.values(statistics.value.money_spent_monthly),
                fill: false,
                tension: 0.4
            },
            {
                label: 'Ganhos',
                data: Object.values(statistics.value.money_received_monthly),
                fill: false,
                tension: 0.4
            }
        ],
    }
}

const setChartOptions = () => {
    return {
        plugins: {
            tooltip: {
                callbacks: {
                    label: function (context) {
                        let label = context.dataset.label || '';

                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    callback: function (value) {
                        return value + ' €';
                    }
                }
            }
        }
    }
}
const setPieData = () => {
    return {
        labels: statistics.value.transactions_by_payment_type.map((item) => item.payment_type),
        datasets: [
            {
                data: statistics.value.transactions_by_payment_type.map((item) => item.total)
            }
        ]
    };
}

const setPieOptions = () => {
    return {
        plugins: {
            legend: {
                labels: {
                    usePointStyle: true,
                }
            }
        }
    };
}

onMounted(async () => {
    await loadStatistics()
    dailyChart.value = setDailyChartData()
    weeklyChart.value = setWeeklyChartData()
    monthlyChart.value = setMonthlyChartData()
    chartOptions.value = setChartOptions()
    pieOptions.value = setPieOptions()
    pieData.value = setPieData()
})
</script>

<template>
    <div class="statistics-container" v-if="!isLoading">
        <div class="cards-container">
            <Card class="stats-card-container">
                <template #title>
                    <div class="flex stat-info"><span>Transações</span> <i class="pi pi-info-circle"
                            v-tooltip.top="'Total de transações'"></i>
                    </div>
                </template>
                <template #content>
                    <p class="m-0">
                        {{ statistics.total_transactions }}
                    </p>
                </template>
            </Card>
            <Card class="stats-card-container">
                <template #title>
                    <div class="flex stat-info"><span>30 dias</span> <i class="pi pi-info-circle"
                            v-tooltip.top="'Dinheiro gasto nos últimos 30 dias'"></i>
                    </div>
                </template>
                <template #content>
                    <p class="m-0">
                        {{ formatter.format(statistics.money_spent_last_30_days) }}
                    </p>
                </template>
            </Card>
            <Card class="stats-card-container">
                <template #title>
                    <div class="flex stat-info"><span>7 dias</span> <i class="pi pi-info-circle"
                            v-tooltip.top="'Dinheiro gasto nos últimos 7 dias'"></i>
                    </div>
                </template>
                <template #content>
                    <p class="m-0">
                        {{ formatter.format(statistics.money_spent_last_7_days) }}
                    </p>
                </template>
            </Card>
            <Card class="stats-card-container">
                <template #title>
                    <div class="flex stat-info"><span>Hoje</span> <i class="pi pi-info-circle"
                            v-tooltip.top="'Dinheiro gasto hoje'"></i>
                    </div>
                </template>
                <template #content>
                    <p class="m-0">
                        {{ formatter.format(statistics.money_spent_today) }}
                    </p>
                </template>
            </Card>
        </div>
        <div class="charts-container">
            <TabView class="statistics-tabs-container">
                <TabPanel header="Gastos/Ganhos Diário">
                    <Chart type="bar" :data="dailyChart" :options="chartOptions" />
                </TabPanel>
                <TabPanel header="Gastos/Ganhos Semanal">
                    <Chart type="bar" :data="weeklyChart" :options="chartOptions" />
                </TabPanel>
                <TabPanel header="Gastos/Ganhos Mensal">
                    <Chart type="line" :data="monthlyChart" :options="chartOptions" />
                </TabPanel>
                <TabPanel header="Transações por Tipo Pagamento">
                    <Chart type="pie" :data="pieData" :options="pieOptions" />
                </TabPanel>
            </TabView>
        </div>
    </div>
    <div class="loading-container" v-else>
        <LoadingSpinner></LoadingSpinner>
    </div>
</template>

<style scoped>
.statistics-container {
    display: flex;
    flex-direction: column;
    gap: 50px;
}

.cards-container {
    display: flex;
    justify-content: space-around;
}


.cards-container .stats-card-container {
    width: 250px;

}

.cards-container .stats-card-container .stat-info {
    justify-content: center;
    align-items: center;
    gap: 12px;
    position: relative;
}

.cards-container .stats-card-container .stat-info i {
    position: absolute;
    right: -10px;
    top: -10px;
    cursor: pointer;
}

.loading-container {
    margin-top: 200px;
}
</style>