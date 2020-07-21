(function() {
	var device;
	var notifs = [];
	// var applicationSid = "AP3219d6e242854380b4fa67e6cb7e2305";
	var remotePhoneNumber = "";

	var defaultNotifOpts={
		"delay": 3000 };
 var longNotifOpts = {
  "delay": 9000000 };
 var dialerCallTimeout = (1000)*1;
 var callerId = null;
 var mainCallerId = null;
 var bMute = false;
 var currentCallSid = null;
 var numberCallFrom = null;

  function cleanup() {
    bMute = false;
  }

  function loadTwilioDevice(token) {
		device = new Twilio.Device(token, {debug: true, allowIncomingWhileBusy: true});
    // Twilio.Device.setup(token, {debug: true});
    device.on('ready', function () {
			$("*[data-twilio-call]").each(function() {
				var number = $( this ).text();
        if ( $( this ).is(":input") ) {
            number = $( this ).val();
        }
        var context = $(this).attr("data-context");
        var id= $(this).attr("data-id");

				var call = $("<button class='btn btn-primary' type='button' id='twillio_call_button'>Call</button>");
				call.click( function() {
	                var numberToCall = number;
	                if (!numberToCall.startsWith("+")) {
	                    numberToCall = "+"+number;
	                }
					callNumber( numberToCall, context, id  );
				} );
				call.insertAfter( this );
			} );

			$(document).on('change', '.call-twilio', function() {
				
				var id = $(this).data('id');
				var numberToCall = $(this).data('phone');
				var context = $(this).data('context');
				var numberCallFrom = $(this).children("option:selected").val();
				$('#show'+id).hide();
				console.log(id);
				console.log(numberToCall);
				console.log(context);
				console.log(numberCallFrom);

				if (!numberToCall.toString().startsWith("+")) {
						numberToCall = "+"+ $(this).data('phone').toString();
				}
				callNumber(numberCallFrom , numberToCall , context , id);
			} );

			$(document).on('click', '.conference-twilio', function() {
				var id = 1;
				var numberToCall = $('#vendors-conference').val();
				var context = $('#context').val();
				var numberCallFrom = $('#conference-number-selected').children("option:selected").val();
				alert('Conferecen Call Initilizing');
				callConference(numberCallFrom , numberToCall , context , id);
			} );
    });

    device.on('error', function (error) {
      console.error("twilio device error ", error);
      showError("Error in Twilio Device");
    });

    device.on('connect', function (conn) {
      console.log("twilio device connected ", conn);
      currentCallSid = conn.parameters.CallSid;
      showNotifTimer("Call with " +remotePhoneNumber);
    });

    device.on('disconnect', function (conn) {
      cleanup();
    });

    device.on('incoming', function (conn) {

			$.getJSON("/twilio/getLeadByNumber?number="+ encodeURIComponent(conn.parameters.From), function( data ) {
        if (data.found) {
          console.log(JSON.stringify(data));
          var name = data.name;
          var number = data.number;
          var confirmed = window.confirm("Incoming call from: "+ name + " on number :" + conn.parameters.From + " would you like to answer call?");


        } else {
            var confirmed = window.confirm("Incoming call from: " + number + " would you like to answer call?");
        }

				if (confirmed) {
					if (data.found) {
						var win = window.open(data.customer_url);
						if (win) {
								//Browser has allowed it to be opened
								win.focus();
						} else {
								//Browser has blocked it
								alert('Please allow popups for this website');
						}
					}
					conn.accept();
				} else {
					conn.reject();
				}
			} );
    });

    device.on('offline', function () {

    });

    device.on('cancel', function () {

    });
  }

  function initializeTwilio() {
    $.getJSON("/twilio/token", function( result ) {
      console.log("Received Twilio Token - agent " + result.agent);
      var token = result.twilio_token;
      loadTwilioDevice( token );
     });
  }

  function callerHangup() {
     showError("Call terminated");
     device.disconnectAll();
  }

  function callerMute(number) {
      var conn = device.activeConnection();
      var el = $(".muter");
      Mute = !bMute;

      if (bMute) {
        conn.mute( true );
        el.text("Unmute");
      } else {
        conn.mute( false );
        el.text("Mute");
      }
  }
  function callNumber(numberCallFrom , number, context, id) {
    var conn = device.activeConnection();
    if (conn) {
      alert("Please hangup current call before dialing new number..");
      return;
    }

		remotePhoneNumber=number;
    	$.notifyClose();
		var callingText = "<h5>Calling " + remotePhoneNumber+"</h5>";
		callingText += "<br/><button class='btn btn-danger' onclick='callerHangup()'>Hangup</button>";

		showWarning(callingText, longNotifOpts);
		var params = {"CallNumber" : numberCallFrom , "PhoneNumber": number, "context": context, "internalId": id};
        console.log("Dialer_StartCall call params", params);
		device.connect(params);
  }

  function closeNotifs(dontClose) {
    if(notifs.length>0 && !dontClose){
        notifs.forEach( function( notif ) {
           notif.close();
        } );
    }

  }
  function showNotif(settings, opts, dontClose) {
        closeNotifs(dontClose);
		opts['delay']=opts['delay']||99999999;
		var notif = $.notify( settings, opts );
		notifs.push( notif );
		return notif;
	}

	function showWarning(message, opts) {
		opts=opts||defaultNotifOpts;
		opts['type']="warning";
		showNotif({ message: message }, opts);
	}
	function showSuccess(message, opts) {
		opts=opts||defaultNotifOpts;
		opts['type']="success";
		showNotif({ message: message },opts);
	}
	function showError(message, opts) {
		opts=opts||defaultNotifOpts;
		opts['type']="danger";
		showNotif({ message: message }, opts);
	}

	function callConference(numberCallFrom , numbers, context, id){
	var conn = device.activeConnection();
    if (conn) {
      alert("Please hangup current call before dialing new number..");
      return;
    }
    
    $.notifyClose();
    
	$.ajax({
    	url: '/api/twilio-conference',
    	type: 'POST',
    	dataType: 'json',
    	data: {
    		_token: "{{ csrf_token() }}",
    		numbersFrom : numberCallFrom,
    		numbers		: numbers,
    		context		: context,
    		id			: id,
    	},
    }).done(function(response) {
    	console.log(response.length)
		for (var i = 1; i < response.length; i++) {
    		 var callingText = "<h5>Calling "+response[i]['number']+"</h5>";
			callingText += "<br/><button class='btn btn-danger' onclick='callerHangup("+ response[i]['number']+")'>Hangup</button>";
			showWarning(callingText, longNotifOpts);		
		}

    })
    .fail(function() {
    	console.log("error");
    });
	}

	function callerConferenceHangup(number){
		$.ajax({
    	url: '/api/twilio-conference',
    	type: 'POST',
    	dataType: 'json',
    	data: {
    		_token: "{{ csrf_token() }}",
    		sid : number,
    	},
    	}).done(function(response) {
    	console.log(response.length)
    	  showError("Caller Removed From Conferece");
    	})
    	.fail(function() {
    	console.log("error");
    	});
	}

	function showNotifTimer(message) {
		var timerInterval=1000;
		var totalSeconds=0;
		var iTime = new Date;
		var myNotif = showNotif({
			message: "" },{
			//onClosed: sipHangUp,
			type: "info",
			delay: 9999999 });
		var main = $("<div></div>");
		var center = $("<center class='c2c-in-call'></center>") ;
		center.appendTo( main );
		var content = $("<div class='content'></div>").appendTo(center);
		$("<span><h2>"+message+"</h2></span>").appendTo(content);
		//timer
		$("<span><h1 class='timer'></h1></span>").appendTo(content);
		$("<hr></hr>").appendTo(center);
		//call control
		var buttons = $("<ul style='list-style: none !important; ' class='buttons'><h4>Call Control</h4></ul>").appendTo(center);
		$("<li><button class='btn btn-danger hangup' onclick='callerHangup()'>Hangup</button></li>").appendTo(buttons);
		$("<li><button class='btn btn-primary muter' onclick='callerMute()'></button></li>").appendTo(buttons);
		function calculateTime()
		{
			++totalSeconds;
			return pad(parseInt(totalSeconds/60))+":"+pad(totalSeconds%60);
		}

		function pad(val)
		{
			var valString = val + "";
			if(valString.length < 2)
			{
			return "0" + valString;
			}
			else
			{
			return valString;
			}
		}
		function onInterval() {
		 var newTime=calculateTime();
		 var muteText ="", holdText = "";
		 if(bMute){
			muteText="Unmute";
		 } else {
			muteText="Mute";
		 }
		 center.find(".timer").text( newTime );
		 center.find(".muter").text( muteText );
		 center.find(".muter").click( callerMute );
		 center.find(".hangup").click( callerHangup );

		 myNotif.update({
			'message':main.html(),
			'type': 'info' });
		 }
		callInterval = setInterval( onInterval, 1000 );
		return myNotif;
	}
	window['initializeTwilio']  =initializeTwilio;
	window['callerHangup'] = callerHangup;
	window['callerMute'] = callerMute;
	window['showNotifTimer'] = showNotifTimer;
})();
