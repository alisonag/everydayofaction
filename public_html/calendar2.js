//=========================================================
//    EDOA Calendar JS 
//    v. 2
//.........................................................
//    Depends on jQuery, CLNDR.js, moment.js, underscore.js
//=========================================================

//----------------------------------------------
//    Fetch the event data  
//----------------------------------------------
var items = [];

$.getJSON( "events-all.json", function( data ) {

  $.each( data, function( page, content ) {

     $.each( data[page], function( key, val ) {
      //console.log(data[page][key]);
           
        var itemObj = {
          title: data[page][key]["title"],
          startDate: moment(data[page][key]["start_date"]).format('YYYY-MM-DD'),
          endDate: moment(data[page][key]["start_date"]).format('YYYY-MM-DD'),
          state: data[page][key]["location"]["region"],
          city: data[page][key]["location"]["locality"],
          image: data[page][key]["image"],
          url: data[page][key]["url"],
          startTime: moment(data[page][key]["start_date"]).format('h:mm a'),
          country: data[page][key]["location"]["country"]
        }     

        items.push( itemObj );

    });//end each item
  }); //end each page of results

//----------------------------------------------
//    Set up the calendar  
//----------------------------------------------
var calendars = {};

$(document).ready( function() {

    // Make sure the dates are happening this month.
    var thisMonth = moment().format('YYYY-MM');
    // Events to load into calendar
    var eventArray = items;

    calendars.clndr1 = $('.cal1').clndr({
        events: eventArray,
        clickEvents: {
            click: function (target) {              
              filterByDate(target);
              attachListeners();
            },
            today: function () {
                
            },
            nextMonth: function () {
                
            },
            previousMonth: function () {
                
            },
            onMonthChange: function () {
              attachListeners();
              setDayColor();
            },
            nextYear: function () {
                
            },
            previousYear: function () {
                
            },
            onYearChange: function () {
                attachListeners();
                setDayColor();
            },
            nextInterval: function () {
                
            },
            previousInterval: function () {
                
            },
            onIntervalChange: function () {
                attachListeners();
                setDayColor();
            }
            
        },
            
        multiDayEvents: {
            singleDay: 'date',
            endDate: 'endDate',
            startDate: 'startDate'
        },
        showAdjacentMonths: true,
        adjacentDaysChangeMonth: false,
        template: $('#template-calendar').html()
    });

    // Bind all clndrs to the left and right arrow keys
    $(document).keydown( function(e) {
        // Left arrow
        if (e.keyCode == 37) {
            calendars.clndr1.back();
        }
        // Right arrow
        if (e.keyCode == 39) {
            calendars.clndr1.forward();
        }
    });

    attachListeners();
    setDayColor();
  }); //end document listen

}); //end json call

//----------------------------------------------
//    Filter Listeners  
//----------------------------------------------
function attachListeners(){
    $('#selectState').change(function(e){
      applyFilters();
    });
}

//----------------------------------------------
//    Filter by State  
//----------------------------------------------
function applyFilters(){
  $('.state').show();
  var state = $( "#selectState option:selected" ).val();
  if(state){ $("div.state:not(."+state+")").hide(); }
   checkEmptiness();
  
}
function checkEmptiness(){
   //check emptiness
  if($("#events-list").find('div.state').filter(function () {
      return $(this).css('display') !== 'none';
    }).length > 0){
    $(".empty-msg").html("");
  } else{
    if($('#selectState option:selected').html()!="The World"){
      var place = " in " + $('#selectState option:selected').html();
    } else {
      var place = "";
    }
    var emptyMsg = "There are no actions"+ place+
                  ". <a href='https://actionnetwork.org/events/new?event_campaign_id=1778'>Organize one in your city.</a>";
    $(".empty-msg").html(emptyMsg);
  }
}

//----------------------------------------------
//    Filter by Date  
//----------------------------------------------
function filterByDate(target){
  console.log(target);

        $('.day').removeClass("active");
      $('.calendar-day-'+target.date._i).addClass("active");

  var eventsSorted = sortEvents(target.events)

  var eventListHtml = "";
  for(var i = 0; i < eventsSorted.length; i++) {
    if(i != 0 && i != eventsSorted.length-1 && eventsSorted[i].state != eventsSorted[i+1].state){
      eventListHtml += '</div><div class="state '+eventsSorted[i].state+'"><h3>'+us_state_abbrevs_names[eventsSorted[i].state]+'</h3>'
    } else if(i == 0){
      eventListHtml += '<div class="state '+eventsSorted[i].state+'"><h3>'+us_state_abbrevs_names[eventsSorted[i].state]+'</h3>'

    }
    eventListHtml += '<div class="event-item"><li class="event">'+
      '<a href="'+eventsSorted[i].url+'">'+
      '<span class="date">'+moment(eventsSorted[i].startDate).format('M/D')+'</span>'+
      '<span class="city">' +eventsSorted[i].city+ '</span>'+
      '<span class="title">'+eventsSorted[i].title +'</span>'+
      '</a></li></div>';
  }
  $('#events-list').html( eventListHtml );

  $('.filterTime').html(moment(target.date).format('MMMM Do, YYYY'))

  checkEmptiness();
}

//----------------------------------------------
//    Sort by state and date
//----------------------------------------------
function sortEvents(eventsArray){

return eventsArray.sort(function(a, b){
  //zzz because international have undefined states and we want them at the bottom of the list
  var o1 = a.state || "zzz"; 
  var o2 = b.state || "zzz";

  var p1 = new Date(a.startDate);
  var p2 = new Date(b.startDate);

  if (o1 < o2) return -1;
  if (o1 > o2) return 1;
  if (p1 < p2) return -1;
  if (p1 > p2) return 1;
  return 0;
});
  
}


function setDayColor(){
  $(".day").each(function(){
    
    var count = $(this).attr("data-events");
   
    var op = ("0" + Math.floor(count*1.2)).slice(-2);
    if(count > 0 && count <= 5){
      op = 20;
      $(this).css("background-color","rgba(4,150,255,."+op+")");
    } else if(count > 5 && count <= 10){
      op = 40;
      $(this).css("background-color","rgba(4,150,255,."+op+")");
    } else if(count > 10 && count <= 15){
      op = 60;
      $(this).css("background-color","rgba(4,150,255,."+op+")");
    }else if(count > 15 && count <= 35){
      op = 80;
      $(this).css("background-color","rgba(4,150,255,."+op+")");
    }else if(count > 35){
      $(this).css("background-color","rgba(4,150,255,1)");
    } else if(count == 0){
      $(this).css("background-color","#eee");
    }
      
  });
  
}



//----------------------------------------------
//    Lookup for state codes  
//----------------------------------------------
var us_state_abbrevs_names = {
  'AL':'ALABAMA',
  'AK':'ALASKA',
  'AS':'AMERICAN SAMOA',
  'AZ':'ARIZONA',
  'AR':'ARKANSAS',
  'CA':'CALIFORNIA',
  'CO':'COLORADO',
  'CT':'CONNECTICUT',
  'DE':'DELAWARE',
  'DC':'DISTRICT OF COLUMBIA',
  'FM':'FEDERATED STATES OF MICRONESIA',
  'FL':'FLORIDA',
  'GA':'GEORGIA',
  'GU':'GUAM GU',
  'HI':'HAWAII',
  'ID':'IDAHO',
  'IL':'ILLINOIS',
  'IN':'INDIANA',
  'IA':'IOWA',
  'KS':'KANSAS',
  'KY':'KENTUCKY',
  'LA':'LOUISIANA',
  'ME':'MAINE',
  'MH':'MARSHALL ISLANDS',
  'MD':'MARYLAND',
  'MA':'MASSACHUSETTS',
  'MI':'MICHIGAN',
  'MN':'MINNESOTA',
  'MS':'MISSISSIPPI',
  'MO':'MISSOURI',
  'MT':'MONTANA',
  'NE':'NEBRASKA',
  'NV':'NEVADA',
  'NH':'NEW HAMPSHIRE',
  'NJ':'NEW JERSEY',
  'NM':'NEW MEXICO',
  'NY':'NEW YORK',
  'NC':'NORTH CAROLINA',
  'ND':'NORTH DAKOTA',
  'MP':'NORTHERN MARIANA ISLANDS',
  'OH':'OHIO',
  'OK':'OKLAHOMA',
  'OR':'OREGON',
  'PW':'PALAU',
  'PA':'PENNSYLVANIA',
  'PR':'PUERTO RICO',
  'RI':'RHODE ISLAND',
  'SC':'SOUTH CAROLINA',
  'SD':'SOUTH DAKOTA',
  'TN':'TENNESSEE',
  'TX':'TEXAS',
  'UT':'UTAH',
  'VT':'VERMONT',
  'VI':'VIRGIN ISLANDS',
  'VA':'VIRGINIA',
  'WA':'WASHINGTON',
  'WV':'WEST VIRGINIA',
  'WI':'WISCONSIN',
  'WY':'WYOMING',
  'AE':'ARMED FORCES AFRICA \ CANADA \ EUROPE \ MIDDLE EAST',
  'AA':'ARMED FORCES AMERICA (EXCEPT CANADA)',
  'AP':'ARMED FORCES PACIFIC',
  'ZZ':'International'
};