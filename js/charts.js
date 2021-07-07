function generate_test_chart(event, response) {
  series = [];
  Object.entries(response.random_data).forEach(
    function (entry) {
      series.push(
        {
          name: entry[0],
          data: [entry[1]],
        }
      )
    }
  );

  var options = {
    series: series,
    chart: {
      type: 'bar',
      height: 150,
      stacked: true,
      stackType: '100%',
      toolbar: {
        show: false
      }
    },
    plotOptions: {
      bar: {
        horizontal: true,
      },
    },
    stroke: {
      width: 1,
      colors: ['#fff']
    },
    yaxis: {
      show: false
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val
        }
      },
      x: {
        show: false
      }
    },
    fill: {
      opacity: 1
    },
    legend: {
      position: 'bottom',
      horizontalAlign: 'left',
      offsetX: 0
    },
    colors: ['#6aa84f', '#ef7c1a']
  };

  new ApexCharts(document.querySelector('#test-page-test-chart'), options).render();
};

jQuery(document).ready(
  function () {
    jQuery('[data-section="chart-test"]').on('seravoAjaxSuccess', generate_test_chart);
  }
);
