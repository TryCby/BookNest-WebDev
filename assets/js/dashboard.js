// assets/js/dashboard.js - จัดการกราฟสถิติแอดมิน (Chart.js)
document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById("categoryChart");
    if (!canvas) return;

    // ดึงข้อมูล JSON จาก attribute data-*
    const chartLabels = JSON.parse(canvas.getAttribute("data-labels") || "[]");
    const chartData = JSON.parse(canvas.getAttribute("data-values") || "[]");

    const ctx = canvas.getContext("2d");
    new Chart(ctx, {
        type: "bar", // เปลี่ยนเป็นกราฟแท่งเรียบง่าย ดูเป็นธรรมชาติ
        data: {
            labels: chartLabels,
            datasets: [{
                label: "ยอดขายรวม (บาท)",
                data: chartData,
                backgroundColor: "#38bdf8", // สีฟ้ามาตรฐาน
                borderColor: "#0288d1",
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false // ไม่ต้องใส่ legend ให้ดูรก
                }
            }
        }
    });
});