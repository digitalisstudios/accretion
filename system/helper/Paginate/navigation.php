<? if($this->_page_count >= 1): ?>
	<div class="row">
		<div class="col-auto">						
			<div class="input-group">
				<select class="form-control pagination-record-count-select" style="border-top-right-radius: 0px !important; border-bottom-right-radius: 0px; -webkit-appearance: none; min-width: 40px">
					<? foreach($this->rpp_array as $rpp_count): ?>
						<option <?=$rpp_count == $this->rpp ? 'selected' : ''?> value="<?=$this->url_without_rpp?><?=$this->_per_page_name?>=<?=$rpp_count?>"><?=$rpp_count?></option>
					<? endforeach; ?>
				</select>
				<span class="input-group-text" style="border-top-left-radius: 0px; border-bottom-left-radius: 0px; border-left: 0">/Page</span>	
			</div>
		</div>
		<div class="col">
			<? if($this->_page_count > 1): ?>
				<nav>
					<ul class="pagination" style="margin:0px;">
						<li class="<?= $this->_page == 1 ? 'disabled' : ''?> page-item">
							<a href="<?=$this->new_url?><?= $this->_page > 1 ? $this->_page_name.'='.($this->prev_group) : '#'; ?>" <? if($this->_page > 1): ?>data-<?=$this->_page_name?>="<?=$x-1?>"<? endif; ?> aria-label="Previous" class="page-link">
								<span aria-hidden="true">&laquo;</span>
							</a>
					    </li>
						<? for($x = $this->start_page; $x <= $this->end_page; $x++): ?>
							<? if($x == 0) continue; ?>
							<li class="<?=$x == $this->_page ? 'active' : ''?> page-item"><a data-<?=$this->_page_name?>="<?=$x?>" <? if($x != $this->_page): ?>href="<?=$this->new_url?><?=$this->_page_name?>=<?=$x?>"<? endif; ?> class="page-link"><?=$x?> <span class="sr-only">(current)</span></a></li>
						<? endfor; ?>
						<li class="<?= $this->_page == $this->_page_count ? 'disabled' : ''?> page-item">
							<a href="<?=$this->new_url?><?= $this->_page < $this->_page_count ? $this->_page_name.'='.($this->next_group) : '#'; ?>" <? if($this->_page < $this->_page_count): ?>data-<?=$this->_page_name?>="<?=$x+1?>"<? endif; ?> aria-label="Next" class="page-link">
								<span aria-hidden="true">&raquo;</span>
							</a>
					    </li>
					</ul>
				</nav>
			<? endif; ?>
		</div>
	</div>
<? endif; ?>

<script>
	$(document).off('change.RPPSelect').on('change.RPPSelect', '.pagination-record-count-select', function(){
		window.location.href = $(this).val();
	});
</script>