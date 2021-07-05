// phpcs:disable PEAR.Functions.FunctionCallSignature
function generateDatabaseBars(JSONdata) {
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

    // Sort the data in a descending order
    var list = [];
    var dataItems = data.length;
    for (var j = 0; j < dataItems; j++) {
    list.push({'datapoint': data[j], 'label': labels[j], 'human': human_vals[j]});
    }

    list.sort(function(a, b) {
      return ((a.datapoint > b.datapoint) ? -1 : ((a.datapoint == b.datapoint) ? 0 : 1));
    });

    for (var k = 0; k < dataItems; k++) {
    data[k] = list[k].datapoint;
    labels[k] = list[k].label;
    human_vals[k] = list[k].human;
    }

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
            position: 'bottom'
          }
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
      },
      legend: {
        show: false
      }
    };

    var chart = new ApexCharts(document.querySelector("#bars_single"), options);
    chart.render();
  }
