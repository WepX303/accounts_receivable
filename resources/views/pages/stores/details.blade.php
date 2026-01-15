@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">

        {{-- Seller Info --}}
        <div class="col-xxl-3">
            <div class="card">
                <div class="card-body p-4">
                    <div>
                        <div class="flex-shrink-0 avatar-md mx-auto">
                            <div class="avatar-title bg-light rounded">
                                <img src="{{ URL::asset('build/images/companies/img-2.png') }}" alt="Force Medicines Logo"
                                    height="50" />
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <h5 class="mb-1">Force Medicines</h5>
                            <p class="text-muted">Since 1987</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0 table-borderless">
                                <tbody>
                                    <tr>
                                        <th><span class="fw-medium">Owner Name</span></th>
                                        <td>David Marshall</td>
                                    </tr>
                                    <tr>
                                        <th><span class="fw-medium">Company Type</span></th>
                                        <td>Partnership</td>
                                    </tr>
                                    <tr>
                                        <th><span class="fw-medium">Email</span></th>
                                        <td>forcemedicines@gamil.com</td>
                                    </tr>

                                    <tr>
                                        <th><span class="fw-medium">Contact No.</span></th>
                                        <td>+(123) 9876 654 321</td>
                                    </tr>

                                    <tr>
                                        <th><span class="fw-medium">Location</span></th>
                                        <td>United Kingdom</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Revenue & Chart --}}
        <div class="col-xxl-9">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Revenue</h4>
                    <div class="btn-group" role="group">
                        <button class="btn btn-soft-primary btn-sm" data-range="daily">Daily</button>
                        <button class="btn btn-soft-secondary btn-sm" data-range="weekly">Weekly</button>
                        <button class="btn btn-soft-secondary btn-sm" data-range="monthly">Monthly</button>
                        <button class="btn btn-soft-secondary btn-sm" data-range="yearly">Yearly</button>
                        <button class="btn btn-soft-secondary btn-sm" data-range="all">All</button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div id="revenue_chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            function formatDate(date) {
                let d = new Date(date);
                let day = String(d.getDate()).padStart(2, '0');
                let month = String(d.getMonth() + 1).padStart(2, '0');
                let year = String(d.getFullYear()).slice(-2);
                return `${day}.${month}.${year}`;
            }

            const chartOptions = {
                chart: {
                    type: 'bar',
                    height: 350
                },
                series: [{
                    name: 'Orders',
                    data: [50, 60, 70, 80, 90, 100, 110] // default günlük veri
                }, {
                    name: 'Earnings ($k)',
                    data: [10, 12, 15, 18, 20, 22, 25]
                }, {
                    name: 'Refunds',
                    data: [2, 1, 3, 2, 4, 3, 2]
                }],
                colors: ['#0ab39c', '#f06548', '#4099ff'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '40%',
                        endingShape: 'rounded'
                    }
                },
                xaxis: {
                    categories: [] // dinamik olarak eklenecek
                },
                yaxis: {
                    title: {
                        text: 'Value'
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#revenue_chart"), chartOptions);

            // Günlük x-axis
            function getDailyLabels(days = 7) {
                let labels = [];
                for (let i = 0; i < days; i++) {
                    let date = new Date();
                    date.setDate(date.getDate() - (days - 1 - i));
                    const options = {
                        weekday: 'short'
                    };
                    let dayName = date.toLocaleDateString('en-US', options);
                    labels.push(`${dayName} (${formatDate(date)})`);
                }
                return labels;
            }

            // Haftalık x-axis
            function getWeeklyLabels(weeks = 4) {
                let labels = [];
                let now = new Date();
                for (let i = weeks - 1; i >= 0; i--) {
                    let start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - now.getDay() - (i * 7));
                    let end = new Date(start);
                    end.setDate(start.getDate() + 6);
                    labels.push(`Week ${weeks-i} (${formatDate(start)}-${formatDate(end)})`);
                }
                return labels;
            }

            // Default: günlük
            chart.updateOptions({
                xaxis: {
                    categories: getDailyLabels()
                }
            });
            chart.render();

            // Butonlar
            const buttons = document.querySelectorAll(".btn-group button");
            buttons.forEach(btn => {
                btn.addEventListener("click", function() {
                    buttons.forEach(b => b.classList.remove("btn-soft-primary"));
                    buttons.forEach(b => b.classList.add("btn-soft-secondary"));
                    this.classList.remove("btn-soft-secondary");
                    this.classList.add("btn-soft-primary");

                    let range = this.getAttribute("data-range");

                    let categories = [];
                    let orders = [];
                    let earnings = [];
                    let refunds = [];

                    switch (range) {
                        case 'daily':
                            categories = getDailyLabels();
                            orders = [50, 60, 70, 80, 90, 100, 110];
                            earnings = [10, 12, 15, 18, 20, 22, 25];
                            refunds = [2, 1, 3, 2, 4, 3, 2];
                            break;
                        case 'weekly':
                            categories = getWeeklyLabels();
                            orders = [300, 400, 350, 500];
                            earnings = [70, 80, 65, 90];
                            refunds = [10, 12, 11, 14];
                            break;
                            // Monthly / Yearly / All örnek basit bırakıldı
                        case 'monthly':
                            categories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                            orders = [1200, 1500, 1300, 1700, 1600, 1800];
                            earnings = [300, 350, 320, 400, 380, 420];
                            refunds = [40, 50, 45, 60, 55, 65];
                            break;
                        case 'yearly':
                            categories = ['2020', '2021', '2022', '2023'];
                            orders = [15000, 17000, 16000, 18000];
                            earnings = [3500, 3800, 3600, 4000];
                            refunds = [450, 500, 480, 520];
                            break;
                        case 'all':
                            categories = ['2020', '2021', '2022', '2023', 'Jan', 'Feb', 'Mar',
                                'Apr', 'May', 'Jun'
                            ];
                            orders = [15000, 17000, 16000, 18000, 1200, 1500, 1300, 1700, 1600,
                                1800];
                            earnings = [3500, 3800, 3600, 4000, 300, 350, 320, 400, 380, 420];
                            refunds = [450, 500, 480, 520, 40, 50, 45, 60, 55, 65];
                            break;
                    }

                    chart.updateOptions({
                        xaxis: {
                            categories: categories
                        },
                        series: [{
                                name: 'Orders',
                                data: orders
                            },
                            {
                                name: 'Earnings ($k)',
                                data: earnings
                            },
                            {
                                name: 'Refunds',
                                data: refunds
                            }
                        ]
                    });
                });
            });

        });
    </script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
