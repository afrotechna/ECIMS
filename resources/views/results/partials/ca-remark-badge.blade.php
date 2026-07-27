@php
    $remark = $result->caModuleRemark();
@endphp
@if($remark === 'PASS')
<span class="badge bg-success">PASS</span>
@elseif($remark === 'FAIL')
<span class="badge bg-danger">FAIL</span>
<span class="text-muted small d-block mt-1" style="font-size:.7rem">Not allowed to sit end-of-semester exam</span>
@else
<span class="text-muted">—</span>
@endif
