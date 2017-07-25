<div class="row">
	<div class="col-md-4">						
		<div class="input-group">
			<select class="form-control pagination-record-count-select">
				<? foreach($this->rpp_array as $rpp_count): ?>
					<option <?=$rpp_count == $this->rpp ? 'selected' : ''?> value="<?=$url_without_rpp?>rpp=<?=$rpp_count?>/"><?=$rpp_count?></option>
				<? endforeach; ?>
			</select>
			<span class="input-group-addon">/Page</span>	
		</div>
	</div>
	<div class="col-md-8">
		<? if($this->_page_count > 1): ?>
			<nav>
				<ul class="pagination" style="margin:0px;">
					<li class="<?= $this->_page == 1 ? 'disabled' : ''?>">
						<a href="<?=$this->new_url?><?= $this->_page > 1 ? 'page='.($this->prev_group) : '#'; ?>" <? if($this->_page > 1): ?>data-page="<?=$x-1?>"<? endif; ?> aria-label="Previous" class="pagination-item">
							<span aria-hidden="true">&laquo;</span>
						</a>
				    </li>
					<? for($x = $this->start_page; $x <= $this->end_page; $x++): ?>
						<? if($x == 0) continue; ?>
						<li class="<?=$x == $this->_page ? 'active' : ''?>"><a data-page="<?=$x?>" href="<?=$this->new_url?><?=$x == $this->_page ? '#' : 'page='.$x.''?>" class="pagination-item"><?=$x?> <span class="sr-only">(current)</span></a></li>
					<? endfor; ?>
					<li class="<?= $this->_page == $this->_page_count ? 'disabled' : ''?>">
						<a href="<?=$this->new_url?><?= $this->_page < $this->_page_count ? 'page='.($this->next_group) : '#'; ?>" <? if($this->_page < $this->_page_count): ?>data-page="<?=$x+1?>"<? endif; ?> aria-label="Next" class="pagination-item">
							<span aria-hidden="true">&raquo;</span>
						</a>
				    </li>
				</ul>
			</nav>
		<? endif; ?>
	</div>
</div>

<hr>

<script>
	$(document).off('change.RPPSelect').on('change.RPPSelect', '.pagination-record-count-select', function(){
		window.location.href = $(this).val();
	});
</script>