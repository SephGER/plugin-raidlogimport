//Element to resize
scale_object = null;
corner = null;
member_id = null;
time_id = null;

//Mouse Position
posx = 0;
startx = 0;
//Object data
oldx = 0;
max_right = 0;
min_left = 0;

//max positions
posi_null = 0;
posi_max = 0;

//input fields
joiner = null;
leaver = null;

document.onmousemove = scale;
document.onmouseup = stop_scale;

function set_member(member_key, px_time) {
	if(!posi_null) {
		posi_null = $('#member_' + member_key).offset();
		posi_null = parseInt(posi_null.left);
	}
	if(!posi_max) {
		posi_max = posi_null + parseInt(px_time);
	}
	member_id = parseInt(member_key);
}

function set_time_key(time_key) {
	time_id = parseInt(time_key.substr(-1));
}

function scale_start(type) {
	var element_id = "times_" + member_id + "_" + time_id;
	scale_object = document.getElementById(element_id);
	joiner = document.getElementById(element_id + "j");
	leaver = document.getElementById(element_id + "l");
	oldx = posx - scale_object.offsetLeft;
	startx = posx;
	corner = type;
    var after = document.getElementById("times_" + member_id + "_" + (time_id+1));
    max_right = posi_max;
    var previous = document.getElementById("times_" + member_id + "_" + (time_id-1));
    min_left = 0;
    if(after != null) {
    	max_right = posi_null + parseInt(after.style.marginLeft) - 1;
    }
    if(previous != null) {
    	min_left = parseInt(previous.style.marginLeft) + parseInt(previous.style.width) + 1;
    }
}

function stop_scale() {
	scale_object = null;
	joiner = null;
	leaver = null;
	startx = 0;
	oldx = 0;
	max_right = 0;
	left_edge = 0;
	right_edge = 0;
	corner = null;
}

function scale(ereignis) {
	posx = document.all ? window.event.clientX : ereignis.pageX;
	if(scale_object != null) {
		if(corner == "left") {
        	set_width((parseInt(scale_object.style.width) + (startx - posx)), true);
			set_left((posx - oldx - posi_null));
		}
		if(corner == "right") {
			set_width(parseInt(scale_object.style.width) + (posx - startx));
		}
		if(corner == "middle") {
			set_left((posx - oldx - posi_null), true);
		}
        startx = posx;
	}
}

function set_width(w, nostopper) {
	var stopper = false;
	if(w > 0) {
		if(w+posi_null+parseInt(scale_object.style.marginLeft) > max_right) {
			w = max_right - parseInt(scale_object.style.marginLeft) - posi_null;
			stopper = true;
		}
        scale_object.style.width = w + "px";
		leaver.value = $('#member_form').data('raid_start') + (parseInt(scale_object.marginLeft) + w)*20;
	} else {
        scale_object.style.width = 1 + "px";
		leaver.value = $('#member_form').data('raid_start') + (parseInt(scale_object.marginLeft) + w)*20;
		stopper = true;
	}
	if(stopper && !nostopper) {
		stop_scale();
	}
}

function set_left(l, nostopper) {
	var stopper = false;
	if(l >= min_left) {
		if(l >= max_right - posi_null - parseInt(scale_object.style.width)) {
			l = max_right - posi_null - parseInt(scale_object.style.width);
			stopper = true;
		}
		scale_object.style.marginLeft = l + "px";
	} else {
		scale_object.style.marginLeft = min_left + "px";
		stopper = true;
	}
    joiner.value = $('#member_form').data('raid_start') + l*20;
    leaver.value = $('#member_form').data('raid_start') + (l + parseInt(scale_object.style.width))*20;
	if(stopper && !nostopper) {
		stop_scale();
	}
}

function change_id_of_input(oldid, newid) {
    $('#' + oldid + "j").attr('name', 'members[' + member_id + '][times][' + newid + '][join]');
    $('#' + oldid + "j").attr('id', "times_" + member_id + "_" + newid + "j");
    $('#' + oldid + "l").attr('name', 'members[' + member_id + '][times][' + newid + '][leave]');
    $('#' + oldid + "l").attr('id', "times_" + member_id + "_" + newid + "l");
    if($('#' + oldid + 's')) {
    	$('#' + oldid + "s").attr('name', 'members[' + member_id + '][times][' + newid + '][extra]');
        $('#' + oldid + "s").attr('id', "times_" + member_id + "_" + newid + "s");
    }
}