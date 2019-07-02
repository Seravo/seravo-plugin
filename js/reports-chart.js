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
