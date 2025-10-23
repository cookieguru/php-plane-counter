<?php
set_time_limit(0);

$redis = new Redis();
$db = new mysqli('localhost');

$types = [];
foreach($redis->hGetAll('adsb:planes:' . date('Y-m-d')) as $hex => $count) {
	if($count < 1) {
		// skip this record, it was probably a decode error
		continue;
	}
	$type = $db->execute_query('SELECT type FROM planes WHERE hex = ?', [$hex])->fetch_object();
	if(!isset($type->type)) {
		$type = (object)['type' => 'unknown'];
	}

	if(!isset($types[$type->type])) {
		$types[$type->type] = 0;
	}
	$types[$type->type]++;
}
arsort($types);
// the code below was AI generated
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8"/>
	<title>Aircraft type count</title>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<style>
		@media (prefers-color-scheme:dark) {
			:root {
				color-scheme:dark;
			}
		}
		html, body {
			margin:0;
			height:100%;
			background:#0e0f13;
			color:#e6e6e6;
			font-family:system-ui, sans-serif;
		}
		.chart-container {
			position:fixed;
			inset:0;
			padding:12px;
			box-sizing:border-box;
		}
		svg {
			width:100%;
			height:100%;
			display:block;
		}
		.slice {
			stroke:#12141a;
			stroke-width:2;
		}
		.label {
			font-size:13px;
			fill:#e6e6e6;
			pointer-events:none;
			paint-order:stroke;
			stroke:rgba(14, 15, 19, 0.6);
			stroke-width:3px;
		}
		.leader-line {
			stroke:#8aa0ff;
			stroke-width:1.5px;
			fill:none;
			opacity:0.9;
		}
		.tooltip {
			position:absolute;
			background:#1a1c24;
			color:#e6e6e6;
			border:1px solid #2a2d3a;
			border-radius:6px;
			padding:8px 10px;
			font-size:12px;
			pointer-events:none;
			box-shadow:0 6px 20px rgba(0, 0, 0, 0.4);
			opacity:0;
			transition:opacity 120ms ease-out, transform 120ms ease-out;
			transform:translate(-50%, -14px);
		}
	</style>
</head>
<body>
<div class="chart-container">
	<svg id="pie" preserveAspectRatio="xMidYMid meet"></svg>
	<div id="tooltip" class="tooltip"></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
<script>
	const rawData = <?php echo json_encode($types, JSON_PRETTY_PRINT); ?>;

	const svg = d3.select('#pie');
	const container = document.querySelector('.chart-container');
	const width = container.clientWidth;
	const height = container.clientHeight;
	svg.attr('viewBox', `0 0 ${width} ${height}`);

	// Shrink pie radius to leave room for labels
	const radius = Math.min(width, height) / 2 * 0.85;

	const g = svg.append('g').attr('transform', `translate(${width / 2}, ${height / 2})`);
	const tooltip = d3.select('#tooltip');

	const data = Object.entries(rawData)
		.map(([k, v]) => ({key: k, value: +v}))
		.filter(d => d.value > 0);

	const total = d3.sum(data, d => d.value);

	const color = d3.scaleOrdinal()
		.domain(data.map(d => d.key))
		.range(d3.schemeTableau10);

	const pie = d3.pie().sort(null).value(d => d.value);
	const arcs = pie(data);

	const arcGen = d3.arc().innerRadius(0).outerRadius(radius);

	// Draw slices
	g.append('g').selectAll('path')
		.data(arcs)
		.join('path')
		.attr('class', 'slice')
		.attr('d', arcGen)
		.attr('fill', d => color(d.data.key))
		.on('mousemove', (event, d) => {
			const offsetX = 12; // pixels to the right
			const offsetY = -24; // pixels above the cursor
			tooltip
				.style('left', `${event.pageX + offsetX}px`)
				.style('top', `${event.pageY + offsetY}px`)
				.style('opacity', 1)
				.html(`<strong>${d.data.key}</strong>: ${d.data.value} (${(d.data.value / total * 100).toFixed(1)}%)`);
		})

		.on('mouseleave', () => tooltip.style('opacity', 0));

	const labelsLayer = g.append('g');
	const linesLayer = g.append('g');

	// Label radius clamped to viewport
	const maxLabelRadius = Math.min(width, height) / 2 - 20;

	arcs.forEach(d => {
		const mid = (d.startAngle + d.endAngle) / 2;
		const midRot = mid - Math.PI / 2;
		const cosA = Math.cos(midRot), sinA = Math.sin(midRot);

		const lineInner = radius * 0.96;
		const lineOuter = radius * 1.02;
		const labelBase = Math.min(radius * 1.05, maxLabelRadius);

		d.lineStart = [cosA * lineInner, sinA * lineInner];
		d.lineEnd = [cosA * lineOuter, sinA * lineOuter];
		d.labelPos = [cosA * labelBase, sinA * labelBase];
		d.side = cosA >= 0 ? 'right' : 'left';
	});

	// Leader lines
	linesLayer.selectAll('line')
		.data(arcs)
		.join('line')
		.attr('class', 'leader-line')
		.attr('x1', d => d.lineStart[0])
		.attr('y1', d => d.lineStart[1])
		.attr('x2', d => d.lineEnd[0])
		.attr('y2', d => d.lineEnd[1]);

	// Labels
	labelsLayer.selectAll('text')
		.data(arcs)
		.join('text')
		.attr('class', 'label')
		.attr('transform', d => `translate(${d.labelPos[0]},${d.labelPos[1]})`)
		.attr('text-anchor', d => d.side === 'right' ? 'start' : 'end')
		.attr('dy', '0.32em')
		.text(d => `${d.data.key}: ${d.data.value}`);

	window.addEventListener('resize', () => location.reload());
</script>
</body>
</html>
