<div class="container" style="max-width:600px;">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Please Login</h3>
		</div>
		<div class="panel-body">
			<form method="post" class="form-horizontal">

				<? if($this->error): ?>
					<div class="alert alert-info" role="alert"><?=$this->error?></div>
				<? endif; ?>
				
				<div class="form-group">
					<label class="control-label col-sm-2">Email</label>
					<div class="col-sm-10">
						<input type="email" name="email" class="form-control" placeholder="example@domain.com">
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-2">Password</label>
					<div class="col-sm-10">
						<input type="password" name="password" class="form-control" placeholder="password">
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-10 col-sm-offset-2">
						<input type="submit" name="login" value="Login" class="btn btn-primary"> <a href="<?=WEB_APP?>Login/forgot_password">Forgot password</a>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>