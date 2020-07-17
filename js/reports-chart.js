// phpcs:disable PEAR.Functions.FunctionCallSignature
function generateChart(JSONdata) {
  var data = [];
  var labels = [];
  var background = [];
  // Create chart colors of string
  Object.keys(JSONdata).forEach(function( folder ) {
    data.push(JSONdata[folder].percentage);
    labels.push(folder + ' - ' + JSONdata[folder].human);
    background.push((new ColorHash({ saturation: [0.9, 0, 1] })).hex(folder));
  });
  // Generate chart
  var ctx = document.getElementById('pie_chart');
  var myPieChart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [
        {
          data: data,
          backgroundColor: background,
          borderWidth: 1
      }
      ]
    },
    options: {
      maintainAspectRatio: true,
      resposive: true,
      tooltips: {
        callbacks: {
          label: function (tooltipItem, data) {
            var label = data.labels[tooltipItem.index] || '';
            if (label) {
              label += ': ';
            }
            label += parseFloat(data.datasets[0].data[tooltipItem.index]).toFixed(2) + '%';
            return label;
          }
        }
      },
      legend: {
        display: true,
        position: 'bottom',
        labels: {
          fontColor: 'black',
          boxWidth: 10
        }
      }
    }
  });
  // Modifies chart legend cursor to pointer
  Chart.plugins.register({
    afterEvent: function (chartInstance, chartEvent) {
      var legend = chartInstance.legend;
      var canvas = chartInstance.chart.canvas;
      var x = chartEvent.x;
      var y = chartEvent.y;
      var cursorStyle = 'default';
      if (
        x <= legend.right
        && x >= legend.left
        && y <= legend.bottom
        && y >= legend.top
      ) {
        var limit = legend.legendHitBoxes.length;
        for (var i = 0; i < limit; ++i) {
          var box = legend.legendHitBoxes[i];
          if (
            x <= box.left + box.width
            && x >= box.left
            && y <= box.top + box.height
            && y >= box.top
          ) {
            cursorStyle = 'pointer';
            break;
          }
        }
      }
      canvas.style.cursor = cursorStyle;
    }
  });
}

function generateDiskDonut(gauge, maximum, unit) {
  var available = Math.round(maximum - gauge);
  var colors = ['#ef7c1a', '#47aedc'];

  if (available < 0) {
    var available = 0;
    var colors = ['#cc0000'];
    jQuery('#disk_use_notification').css("display", "block");
  }

  // Generate donut chart
  var options = {
    series: [Math.round(gauge), available],
    colors: colors,
    chart: {
      type: 'donut',
      height: 120,
    },
    labels: [seravo_site_status_loc.used, seravo_site_status_loc.available],
    responsive: [{
      breakpoint: 480
    }],
    legend: {
      show: false
    },
    dataLabels: {
      enabled: false,
    },
    tooltip: {
      enabled: true,
      x: {
        show: false
      },
      y: {
        formatter: function(value){
          return value.toString() + unit;
        }
      },
    }
};

  var chart = new ApexCharts(document.querySelector("#donut_single"), options);
  chart.render();
}

function generateDiskBars(JSONdata) {
  var data = [];
  var labels = [];
  var human_vals = [];

  var axis = {
    categories: labels,
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    },
    labels: {
      show: false
    }
  };

  Object.keys(JSONdata).forEach(function( folder ) {
    data.push(JSONdata[folder].percentage);
    labels.push(folder);
    human_vals.push(JSONdata[folder].human);
  });

  var options = {
    series: [{
      data: data
    }],
    colors: ['#44A1CB'],
    chart: {
      type: 'bar',
      height: labels.length * 40,
      toolbar: {
        show: false,
      }
    },
    plotOptions: {
      bar: {
        horizontal: true,
        dataLabels: {
          position: 'bottom',
        },
      }
    },
    dataLabels: {
      enabled: true,
      textAnchor: 'start',
      style: {
        colors: ['#444']
      },
      formatter: function (val, opt) {
        return opt.w.globals.labels[opt.dataPointIndex] + ": " + human_vals[opt.dataPointIndex];
      },
      offsetX: 0,
      dropShadow: {
        enabled: false
      }
    },
    xaxis: axis,
    yaxis: axis,
    grid: {
      show: false,
      padding: {
       left: 0,
       right: 0,
       top: 0,
       bottom: 0
      }
    },
    tooltip: {
      enabled: true,
      custom: function({series, seriesIndex, dataPointIndex, w}) {
        return '<div class="arrow_box">' +
          '<span>' + w.globals.labels[dataPointIndex] + ": " + human_vals[dataPointIndex] + '</span>' +
          '</div>'
      },
    }
  };

  var chart = new ApexCharts(document.querySelector("#bars_single"), options);
  chart.render();
}
