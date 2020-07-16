function generateRedisHitChart(hits, misses) {

  var options = {
    series: [{
      name: seravo_site_status_loc.keyspace_hits,
      data: [hits]
    }, {
      name: seravo_site_status_loc.keyspace_misses,
      data: [misses]
    }],
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

  var redis_hit_chart = new ApexCharts(document.querySelector("#redis-hit-rate-chart"), options);
  redis_hit_chart.render();
};


function generateHTTPHitChart(hits, misses, stales) {

  var options = {
    series: [{
      name: seravo_site_status_loc.hits,
      data: [hits]
    }, {
      name: seravo_site_status_loc.misses,
      data: [misses]
    }, {
      name: seravo_site_status_loc.stales,
      data: [stales]
    }],
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
    colors: ['#6aa84f', '#ef7c1a', '#47aedc']
  };

  var http_hit_chart = new ApexCharts(document.querySelector("#http-hit-rate-chart"), options);
  http_hit_chart.render();
};
