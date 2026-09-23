@props(['name', 'size' => 40])

@php
    $colors = ['#1abc9c','#2ecc71','#3498db','#9b59b6','#34495e','#16a085','#27ae60','#2980b9','#8e44ad','#2c3e50','#f1c40f','#e67e22','#e74c3c','#d35400','#c0392b'];
    $hasName = !empty($name);
    $initial = $hasName ? mb_strtoupper(mb_substr($name, 0, 1)) : '';
    $sum = 0;
    if ($hasName) {
        for ($i = 0; $i < mb_strlen($name); $i++) {
            $sum += mb_ord(mb_substr($name, $i, 1));
        }
    }
    $color = $colors[$sum % count($colors)];
    $fontSize = round($size * 0.45);
@endphp

@if($hasName)
    <div class="avatar-initial" style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ $fontSize }}px;background-color:{{ $color }};">{{ $initial }}</div>
@else
    <div class="avatar-placeholder" style="width:{{ $size }}px;height:{{ $size }}px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"><path d="M20,4.211a7.191,7.191,0,0,0-7,7.368,7.191,7.191,0,0,0,7,7.368,7.191,7.191,0,0,0,7-7.368,7.191,7.191,0,0,0-7-7.368ZM9,11.579C9,5.184,13.925,0,20,0S31,5.184,31,11.579,26.075,23.158,20,23.158,9,17.974,9,11.579Zm11,20a22.19,22.19,0,0,0-16.545,7.76,1.93,1.93,0,0,1-2.827.088,2.184,2.184,0,0,1-.083-2.976A26.1,26.1,0,0,1,20,27.368,26.1,26.1,0,0,1,39.455,36.45a2.184,2.184,0,0,1-.083,2.976,1.93,1.93,0,0,1-2.827-.088A22.19,22.19,0,0,0,20,31.579Z" fill="currentColor" fill-rule="evenodd"/></svg></div>
@endif
