@extends('parts.template')

@section('title')
Home
@endsection


@section('content')
<div class="page-loader">
	<div class="page-loader__spinner">
		<svg viewBox="25 25 50 50">
			<circle cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
		</svg>
	</div>
</div>
@if($filter=='data')
<font color="#b2b2b2" size="5"><b>AUDIT TRAIL - DATA ENTRY</b></font><br>
@else
<font color="#b2b2b2" size="5"><b>AUDIT TRAIL - SESSION LOG</b></font><br>
@endif
<br>
<!--
<ol class="breadcrumb">
	<li class="breadcrumb-item active">Index</li>
</ol> 
-->

 
			<?php 
			for ($x = 0; $x < sizeof($audits); $x++) {    ?>
				
<div class="card">
	<div class="card-body">
		<div class="section">
				<?php
				for ($y = 0; $y < sizeof($audits[$x]); $y++) {   
					echo '<b>Audit Id : </b>'.$audits[$x][$y]->id.'<br>';
					echo '<b>Audit Model : </b>'.ucfirst($audits[$x][$y]->auditable_type).'<br>';
					echo '<b>Audit Event : </b>'.ucfirst($audits[$x][$y]->event).'<br>';
					echo '<b>Audit URL : </b>'.$audits[$x][$y]->url.'<br>';
					echo '<b>Audit By : </b>'.$audits[$x][$y]->user_id.'<br>';
					echo '<b>Audit Logs : </b><br>';

					$myKey = array_keys($audits[$x][$y]->old_values);
					
					if(sizeof($audits[$x][$y]->new_values)>0){
						$myKey2 = array_keys($audits[$x][$y]->new_values);
					}

					$auditOldList = [];
					$auditNewList = [];

					foreach ($audits[$x][$y]->old_values as $myJSON2) {
						$auditOldList[] = $myJSON2;			
					}
					
					foreach ($audits[$x][$y]->new_values as $myJSON2) {
						$auditNewList[] = $myJSON2;			
					}
 					
					for ($z = 0; $z < sizeof($audits[$x][$y]->old_values); $z++) {  
		 
						echo ($z+1).'. '.'Change '.$myKey[$z].' from <font color="green">'.$auditOldList[$z].'</font> to <font color="green">'.$auditNewList[$z].'</font><br>';  
					}
					
					if(sizeof($audits[$x][$y]->old_values)<1){
						for ($z = 0; $z < sizeof($audits[$x][$y]->new_values); $z++) {  

							echo ($z+1).' .'.ucfirst($myKey2[$z]).'<font color="green"> '.$auditNewList[$z].'</font><br>';  
						}
					}
					echo '<font color="DodgerBlue">More Info ...</font><br>';

 				} 
 				?>
						</div>
					</div>
				</div>
				<?php
			} 
			?>



@endsection
