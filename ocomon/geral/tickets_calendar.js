// function showCalendar(div, worker_id = '') {
function showCalendar(div, args) {
    // document.addEventListener("DOMContentLoaded", function() {
        
        const backgroundColorOpened = $('#opened-colors').css('background-color');
        const colorOpened = $('#opened-colors').css('color');
        const borderColorOpened = $('#opened-colors').css('border-color');

        const backgroundColorClosed = $('#closed-colors').css('background-color');
        const colorClosed = $('#closed-colors').css('color');
        const borderColorClosed = $('#closed-colors').css('border-color');

        const backgroundColorScheduled = $('#scheduled-colors').css('background-color');
        const colorScheduled = $('#scheduled-colors').css('color');
        const borderColorScheduled = $('#scheduled-colors').css('border-color');


        let defaults = {
            worker_id: '',
            client: '',
            area: '',
            opened: true,
            closed: false,
            scheduled: true
        }

        let params = {...defaults, ...args};

        // console.log('worker_id: ' + params.worker_id);
        // console.log('client: ' + params.client);
        // console.log('area: ' + params.area);
        // console.log('opened: ' + params.opened);
        // console.log('closed: ' + params.closed);
        // console.log('scheduled: ' + params.scheduled);
       

        let eventSources = [];
        
        if (params.scheduled == true) {
            eventSources.push ({
                url: './get_calendar_events_scheduled.php',
                    method: 'POST',
                    extraParams: {
                        color: colorScheduled,
                        bgColor: backgroundColorScheduled,
                        borderColor: borderColorScheduled,
                        worker_id: params.worker_id,
                        area: params.area,
                        client: params.client,
                        function() {
                            return {
                                cachebuster: new Date().valueOf()
                            };
                        },
                    },
                    failure: function() {
                        alert('there was an error while fetching events from: get_calendar_events_scheduled');
                    },
            });
        }
        
        if (params.opened == true) {
            eventSources.push ({
                url: './get_calendar_events_started.php',
                    method: 'POST',
                    extraParams: {
                        color: colorOpened,
                        bgColor: backgroundColorOpened,
                        borderColor: borderColorOpened,
                        worker_id: params.worker_id,
                        area: params.area,
                        client: params.client,
                        function() {
                            return {
                                cachebuster: new Date().valueOf()
                            };
                        },
                    },
                    failure: function() {
                        alert('there was an error while fetching events from: get_calendar_events_started');
                    },
            });
        }

        if (params.closed == true) {
            eventSources.push ({
                url: './get_calendar_events_closed.php',
                    method: 'POST',
                    extraParams: {
                        color: colorClosed,
                        bgColor: backgroundColorClosed,
                        borderColor: borderColorClosed,
                        worker_id: params.worker_id,
                        area: params.area,
                        client: params.client,
                        function() {
                            return {
                                cachebuster: new Date().valueOf()
                            };
                        },
                    },
                    failure: function() {
                        alert('there was an error while fetching events from: get_calendar_events_closed');
                    },
            });
        }
        
    
    
        let calendarEl = document.getElementById(div);
        let calendar = new FullCalendar.Calendar(calendarEl, {
            schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
            themeSystem: 'bootstrap',
            // handleWindowResize: true,
            // locale: 'pt-br',
            initialView: "dayGridMonth",
            eventDisplay: 'block',
            selectable: true,
            dayMaxEventRows: 4, // for all non-TimeGrid views
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            height: 600,
            eventSources: eventSources,
            eventTimeFormat: { // like '14:30:00'
                hour: '2-digit',
                minute: '2-digit',
                meridiem: false
            },
            eventClick: function(info) {

                info.jsEvent.preventDefault(); // don't let the browser navigate

                $('#eventTicketId').val(info.event.id);
                $('#eventTicketUrl').val(info.event.url);
                $('#calTicketNum').text(info.event.id);
                $('#status').text(info.event.extendedProps.status);
                $('#openingDate').text(info.event.extendedProps.data_abertura);
                $('#scheduledTo').text(info.event.extendedProps.oco_scheduled_to);
                $('#doneDate').text(info.event.extendedProps.data_fechamento);
                $('#client').text(info.event.extendedProps.cliente);
                $('#openedBy').text(info.event.extendedProps.aberto_por);
                $('#department').text(info.event.extendedProps.local);
                $('#requesterArea').text(info.event.extendedProps.area_solicitante);
                $('#responsibleArea').text(info.event.extendedProps.area_atendimento);
                $('#issueType').text(info.event.extendedProps.problema);
                $('#operator').text(info.event.extendedProps.operador);
                $('#workers').text(info.event.extendedProps.funcionarios);
                $('#description').html(info.event.extendedProps.descricao);

                $('#modalEvent').modal('show');

                // if (info.event.url) {
                //     window.open(info.event.url);
                // }
                
                // // change the border color just for fun
                // info.el.style.borderColor = 'red';
            },
            dateClick: function(info) {
                // alert('Clicked on: ' + info.dateStr);
                // alert('Current view: ' + info.view.type);
                // change the day's background color just for fun
                // info.dayEl.style.backgroundColor = 'red';
                if (info.view.type == 'dayGridMonth') {
                    calendar.changeView('timeGridDay', info.dateStr);
                }
            },
            windowResize: function(arg) {
                calendar.updateSize();
            }

        });
        calendar.setOption('locale', 'pt-br');

        // var view = calendar.view;
        // console.log("The view's currentStart " + view.currentStart);
        // $('#info-view').text(view.currentStart);


        // if ($('#info-view').text != '') {
            // calendar.gotoDate(new Date($('#info-view').text))
        // }
        
        // calendar.refetchEvents();
        calendar.render();
        // calendar.next();

        return calendar;
        
        // calendar.updateSize();
    // });
}