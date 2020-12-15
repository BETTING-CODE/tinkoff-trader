<!--<link rel="stylesheet" href="https://cdn.rawgit.com/Chalarangelo/mini.css/v3.0.1/dist/mini-default.min.css">-->
<script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>
<style>
    * {
        font-family: Arial, Helvetica, sans-serif;
    }

    .list-stocks {
        width: 450px;
        display: inline-block;
        vertical-align: top;
    }

    .stock-container {
        width: 600px;
        display: inline-block;
        vertical-align: top;
    }

    table.stocks tr:hover {
        background-color: gray;
        cursor: pointer;
    }

    .stocks .active {
        background-color: darkorange;
    }

    .warning {
        background-color: cyan;
    }

    .currencies_info {
        display: inline-block;
        vertical-align: top;
        margin-right: 10px;
    }

    .positive {
        background-color: greenyellow;
    }

    .negative {
        background-color: red;
    }
</style>
<script>
    function exponentialMovingAverage(array, range) {
        const k = 2 / (range + 1);
        let emaArray = [array[0]];
        for (var i = 1; i < array.length; i++) {
            emaArray.push(array[i] * k + emaArray[i - 1] * (1 - k));
        }
        return emaArray;
    }

    function onClickStock(stock) {
        
        const apiFinhub = 'bq1h7e7rh5rd509cma90';

        fetch(`https://finnhub.io/api/v1/stock/recommendation?symbol=${stock}&token=${apiFinhub}`)
            .then(data => data.json())
            .then(data => {
                const recommedation = document.querySelector('.recommedation')
                const rec = data[0];
         
                let all = rec.hold + 2 * rec.strongBuy + rec.buy + 2 * rec.strongSell + rec.sell
                let hold = (rec.hold / all * 100).toFixed()
                let buy = ((2 * rec.strongBuy + rec.buy) / all * 100).toFixed()
                let sell = ((2 * rec.strongSell + rec.sell) / all * 100).toFixed()

                let string = `<p>
            Время : ${rec.period}
        </p><p>
            Нужно ли держать считают : <b>${hold}%</b> трейдеров 
        </p><p>
            За покупку : <b>${buy}%</b> трейдеров
        </p><p>
            За продажу : <b>${sell}%</b> трейдеров
        </p>`

                recommedation.innerHTML = '';
                recommedation.innerHTML += string;
            })

        fetch(`https://finnhub.io/api/v1/stock/profile2?symbol=${stock}&token=${apiFinhub}`)
            .then(data => data.json())
            .then(data => {
                const profile = document.querySelector('.profile')
                let string = `<p>
            Компания торгуется на <b>${data.exchange}</b> бирже и находится в <b>${data.finnhubIndustry}</b> индустрии
        </p>`

                profile.innerHTML = '';
                profile.innerHTML += string;
            })

        fetch(`https://finnhub.io/api/v1/scan/technical-indicator?symbol=${stock}&resolution=D&token=${apiFinhub}`)
            .then(data => data.json())
            .then(data => {
                const techanalysis = document.querySelector('.techanalysis')
                const string = `<p>
            Сигнал по техническим индикаторам <b>${data.technicalAnalysis.signal}</b>
        </p>`

                techanalysis.innerHTML = '';
                techanalysis.innerHTML += string;
            })

        const todayDate = new Date()
        const todayString = todayDate.toISOString().slice(0, 10);
        todayDate.setDate(todayDate.getDate() - 2);
        const ttodayString = todayDate.toISOString().slice(0, 10);
        fetch(`https://finnhub.io/api/v1/company-news?symbol=${stock}&from=${ttodayString}&to=${todayString}&token=${apiFinhub}`)
            .then(data => data.json())
            .then(data => {

                const news = data
                const newsHTML = document.querySelector('.company-news')

                let string = '<ul>'
                for (let i = 0; i < news.length; i++) {
                    string += `<li>
                <h2>${news[i].source} : ${news[i].headline}</h2>
                <h5>${new Date(news[i].datetime*1000)}</h5>
                <p>${news[i].summary}</p>
                <a href="${news[i].url}" target="_blank">${news[i].url}</a>
            </li>`
                }
                string += '</ul>'

                newsHTML.innerHTML = '';
                newsHTML.innerHTML += string;

            })

        fetch(`./api.php?s=${stock}`)
            .then(res => res.json())
            .then(res => {
                const data = res

                document.querySelector('.chart').innerHTML = ''

                let stocks = document.querySelectorAll('.stocks tr')
                for (let i = 0; i < stocks.length; i++) {
                    stocks[i].classList.remove('active');
                    if (stocks[i].getAttribute('data-stock') == stock) {
                        stocks[i].classList.add('active')
                    }
                }


                const width = document.querySelector('.stock-container').offsetWidth
                const height = 450
                let container = document.createElement('div')
                const chart = LightweightCharts.createChart(document.querySelector('.chart'), {
                    width: width,
                    height: height
                })
                document.querySelector('.chart').appendChild(container)

                let dataChart = []
                let arrayTimeClosePrice = []
                let volume = []
                let maxVolume = 0

                const minColorVolume = 'rgba(0, 150, 136, 0.8)'
                const maxColorVolume = 'rgba(255,82,82, 0.8)'

                let options = {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                }

                data.map(res => {
                    let date = new Date(res.time.date)
                    date = date.toLocaleDateString('en-US', options)

                    volume.push({
                        time: date,
                        value: res.volume
                    })
                    if (res.v > maxVolume) {
                        maxVolume = res.volume
                    }

                    dataChart.push(res.close)

                    arrayTimeClosePrice.push({
                        time: date,
                        value: res.close
                    })
                })

                maxVolume = maxVolume * 0.5

                volume.map(v => {
                    v.color = (v.value > maxVolume) ? maxColorVolume : minColorVolume
                    return v
                })

                let ema3 = exponentialMovingAverage(dataChart, 3)
                let ema5 = exponentialMovingAverage(dataChart, 5)
                let ema8 = exponentialMovingAverage(dataChart, 8)
                let ema10 = exponentialMovingAverage(dataChart, 10)
                let ema12 = exponentialMovingAverage(dataChart, 12)
                let ema15 = exponentialMovingAverage(dataChart, 15)

                let ema35 = exponentialMovingAverage(dataChart, 35)
                let ema40 = exponentialMovingAverage(dataChart, 40)
                let ema50 = exponentialMovingAverage(dataChart, 50)
                let ema60 = exponentialMovingAverage(dataChart, 60)

                let ema_min = []
                let ema_max = []
                for (let i = 0; i < dataChart.length; i++) {
                    ema_min.push({
                        time: arrayTimeClosePrice[i].time,
                        value: Math.min(ema3[i], ema5[i], ema8[i], ema10[i], ema12[i], ema15[i])
                    })

                    ema_max.push({
                        time: arrayTimeClosePrice[i].time,
                        value: Math.max(ema35[i], ema40[i], ema50[i], ema60[i])
                    })
                }

                const areaSeries = chart.addAreaSeries({
                    lineColor: 'rgba(67, 83, 254, 1)',
                    lineWidth: 1,
                });
                areaSeries.setData(arrayTimeClosePrice)

                const emaMaxLineSeries = chart.addLineSeries({
                    color: 'rgba(255,0,0,1)',
                    lineWidth: 1
                })
                emaMaxLineSeries.setData(ema_max)

                const emaMinLineSeries = chart.addLineSeries({
                    color: 'rgba(255,165,0,1)',
                    lineWidth: 1
                })
                emaMinLineSeries.setData(ema_min)

                var volumeSeries = chart.addHistogramSeries({
                    color: '#26a69a',
                    priceFormat: {
                        type: 'volume',
                    },
                    priceScaleId: '',
                    scaleMargins: {
                        top: 0.8,
                        bottom: 0,
                    },
                });
                volumeSeries.setData(volume)

                let toolTip = document.createElement('div');
                toolTip.className = 'three-line-legend';
                container.appendChild(toolTip);
                toolTip.style.display = 'block';
                toolTip.style.left = 3 + 'px';
                toolTip.style.top = 3 + 'px';

                function setLastBarText() {

                    const textEmaMin = ema_min[ema_min.length - 1].value
                    const textEmaMax = ema_max[ema_max.length - 1].value
                    const price = arrayTimeClosePrice[arrayTimeClosePrice.length - 1].value

                    toolTip.innerHTML = '<div style="font-size: 24px; margin: 4px 0px; color: #20262E">' + stock + '</div>' +
                        '<div style="font-size: 22px; margin: 4px 0px; color: #20262E">Цена: ' + (Math.round(price * 100) / 100).toFixed(2) + '</div>' +
                        '<div>Макс скользящие средние: <span style="color:red">' + (Math.round(textEmaMax * 100) / 100).toFixed(2) + '</span></div>' +
                        '<div>Мин скользящие средние: <span style="color:orange">' + (Math.round(textEmaMin * 100) / 100).toFixed(2) + '</span></div>';
                }

                setLastBarText()

                chart.subscribeCrosshairMove(function(param) {
                    if (param === undefined || param.time === undefined || param.point.x < 0 || param.point.x > width || param.point.y < 0 || param.point.y > height) {
                        setLastBarText();
                    } else {

                        const textEmaMin = param.seriesPrices.get(emaMinLineSeries)
                        const textEmaMax = param.seriesPrices.get(emaMaxLineSeries)
                        const price = param.seriesPrices.get(areaSeries)

                        toolTip.innerHTML = '<div style="font-size: 24px; margin: 4px 0px; color: #20262E">' + stock + '</div>' +
                            '<div style="font-size: 22px; margin: 4px 0px; color: #20262E">Цена: ' + (Math.round(price * 100) / 100).toFixed(2) + '</div>' +
                            '<div>Макс скользящие средние: <span style="color:red">' + (Math.round(textEmaMax * 100) / 100).toFixed(2) + '</span></div>' +
                            '<div>Мин скользящие средние: <span style="color:orange">' + (Math.round(textEmaMin * 100) / 100).toFixed(2) + '</span></div>';

                    }
                })

                chart.timeScale().fitContent();

                if (markers.length > 0) {
                    const lengthMarkers = markers.length - 1
                    const buy = markers[lengthMarkers].value

                    const lengthArrayTimeClosePrice = arrayTimeClosePrice.length - 1
                    const sell = arrayTimeClosePrice[lengthArrayTimeClosePrice].value

                    if (sell > buy) {
                        const stringDateSell = `${arrayTimeClosePrice[lengthArrayTimeClosePrice].time.month}-${arrayTimeClosePrice[lengthArrayTimeClosePrice].time.day}-${arrayTimeClosePrice[lengthArrayTimeClosePrice].time.year}`
                        const dateBuy = new Date(markers[lengthMarkers].time).toLocaleDateString('ru-RU', options)
                        const dateSell = new Date(stringDateSell).toLocaleDateString('ru-RU', options)
                    }
                }
            })
    }
</script>
<div class="container">
    <div class="row">
        <?php include './getAccountInfo.php' ?>
    </div>
    <div class="row">
        <div class="list-stocks">
            <?php
                sleep(5);
                include './getChannelDonchianProfitStocks.php'
            ?>
        </div>
        <div class="stock-container">
            <div class="chart"></div>
            <div class="profile">
            </div>
            <div class="recommedation">
            </div>
            <div class="techanalysis">
            </div>
            <div class="company-news">
            </div>
        </div>
    </div>
</div>