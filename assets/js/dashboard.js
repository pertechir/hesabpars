// Dashboard Scripts
document.addEventListener('DOMContentLoaded', function() {
    // Loading Animation
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(loadingOverlay);

    // Initialize all charts after data is loaded
    Promise.all([
        fetchSalesData(),
        fetchCustomerData(),
        fetchRevenueData(),
        fetchProductData()
    ]).then(([salesData, customerData, revenueData, productData]) => {
        initializeSalesChart(salesData);
        initializeCustomerChart(customerData);
        initializeRevenueChart(revenueData);
        initializeProductChart(productData);
        
        // Remove loading overlay
        loadingOverlay.remove();
    }).catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'خطا در بارگذاری داده‌ها',
            text: 'لطفاً صفحه را مجدداً بارگذاری کنید.',
            confirmButtonText: 'تلاش مجدد'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    });

    // Event Listeners for Chart Period Selection
    document.querySelectorAll('.chart-period-select').forEach(select => {
        select.addEventListener('change', function(e) {
            const chartId = this.dataset.chart;
            const period = this.value;
            updateChartData(chartId, period);
        });
    });
});

// Fetch Functions
async function fetchSalesData() {
    // Simulated API call
    return new Promise(resolve => {
        setTimeout(() => {
            resolve({
                labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
                data: [450, 600, 450, 700, 500, 800]
            });
        }, 1000);
    });
}

async function fetchCustomerData() {
    return new Promise(resolve => {
        setTimeout(() => {
            resolve({
                labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
                data: [120, 150, 180, 200, 160, 210]
            });
        }, 1000);
    });
}

async function fetchRevenueData() {
    return new Promise(resolve => {
        setTimeout(() => {
            resolve({
                labels: ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'],
                data: [
                    [30, 40, 35, 50, 49, 60, 70],
                    [20, 35, 40, 45, 50, 55, 60]
                ]
            });
        }, 1000);
    });
}

async function fetchProductData() {
    return new Promise(resolve => {
        setTimeout(() => {
            resolve({
                labels: ['محصول A', 'محصول B', 'محصول C', 'محصول D', 'محصول E'],
                data: [44, 55, 41, 37, 22]
            });
        }, 1000);
    });
}

// Chart Initialization Functions
function initializeSalesChart(data) {
    const options = {
        series: [{
            name: 'فروش',
            data: data.data
        }],
        chart: {
            type: 'area',
            height: 350,
            fontFamily: 'Anjoman',
            toolbar: {
                show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            categories: data.labels
        },
        yaxis: {
            title: {
                text: 'میزان فروش (میلیون تومان)'
            }
        },
        colors: ['#2196F3'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value + ' میلیون تومان';
                }
            }
        }
    };

    const chart = new ApexCharts(document.querySelector('#salesChart'), options);
    chart.render();
}

function initializeCustomerChart(data) {
    const options = {
        series: [{
            name: 'مشتریان جدید',
            data: data.data
        }],
        chart: {
            type: 'bar',
            height: 350,
            fontFamily: 'Anjoman',
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                columnWidth: '60%',
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: data.labels
        },
        yaxis: {
            title: {
                text: 'تعداد مشتریان'
            }
        },
        colors: ['#9c27b0'],
        fill: {
            opacity: 0.8
        }
    };

    const chart = new ApexCharts(document.querySelector('#customerChart'), options);
    chart.render();
}

function initializeRevenueChart(data) {
    const options = {
        series: [{
            name: 'درآمد',
            data: data.data[0]
        }, {
            name: 'هزینه',
            data: data.data[1]
        }],
        chart: {
            type: 'line',
            height: 350,
            fontFamily: 'Anjoman',
            toolbar: {
                show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: [3, 3],
            curve: 'smooth',
            dashArray: [0, 5]
        },
        xaxis: {
            categories: data.labels
        },
        yaxis: {
            title: {
                text: 'میلیون تومان'
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value + ' میلیون تومان';
                }
            }
        },
        grid: {
            borderColor: '#f1f1f1'
        },
        legend: {
            position: 'top'
        },
        colors: ['#4caf50', '#f44336']
    };

    const chart = new ApexCharts(document.querySelector('#revenueChart'), options);
    chart.render();
}

function initializeProductChart(data) {
    const options = {
        series: data.data,
        chart: {
            type: 'donut',
            height: 350,
            fontFamily: 'Anjoman'
        },
        labels: data.labels,
        colors: ['#2196F3', '#9c27b0', '#4caf50', '#ff9800', '#f44336'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        plotOptions: {
            pie: {
                donut: {
                    size: '70%'
                }
            }
        }
    };

    const chart = new ApexCharts(document.querySelector('#productChart'), options);
    chart.render();
}

function updateChartData(chartId, period) {
    // Show loading state
    Swal.fire({
        title: 'در حال بارگذاری...',
        text: 'لطفاً صبر کنید',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Simulate API call
    setTimeout(() => {
        // Update chart data based on period
        // This is where you would normally make an API call

        Swal.close();
    }, 1000);
}

// Utility Functions
function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

function formatDate(date) {
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(date).toLocaleDateString('fa-IR', options);
}