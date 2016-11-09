<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>
<script type="text/javascript">
$( function() {
	$( ".filter-date" ).datepicker({
	  dateFormat: "yy-mm-dd"
	});
});
</script>
<script src="js/list.min.js"></script>
<script type="text/javascript">
var options = {
valueNames: [ <?php echo "'".implode( "', '",$col_slugs )."'"; ?> ]
};
var userList = new List('users', options);
</script>

</body><!-- end body as main contaivaner -->
</html>
