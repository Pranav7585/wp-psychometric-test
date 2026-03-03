/* WP Psychometric Test Pro — Frontend Application */
/* global PsychoApp, Chart */
(function ( $ ) {
    'use strict';

    var App = {
        session_key:   '',
        participant:   null,
        current_test:  null,
        test_data:     null,
        timer_int:     null,
        time_elapsed:  0,
        chart_inst:    null,

        /* -----------------------------------------------
           INIT
        ----------------------------------------------- */
        init: function () {
            App.session_key = PsychoApp.session_key || '';
            App.participant  = PsychoApp.participant  || null;

            // If session exists show tests, otherwise show entry
            if ( App.session_key && App.participant ) {
                $( '#psycho-welcome-name' ).text( App.participant.name );
                App.showStep( 'tests' );
            } else {
                App.showStep( 'entry' );
            }

            App.bindEntry();
            App.bindTestList();
            App.bindExam();
            App.bindResults();
        },

        /* -----------------------------------------------
           SHOW STEP
        ----------------------------------------------- */
        showStep: function ( id ) {
            $( '.psycho-step' ).hide();
            $( '#psycho-step-' + id ).show();
            $( 'html, body' ).animate( {
                scrollTop: $( '#psycho-portal' ).offset().top - 60
            }, 300 );
        },

        /* -----------------------------------------------
           SHOW MESSAGE
        ----------------------------------------------- */
        showMsg: function ( selector, msg, type ) {
            type = type || 'info';
            $( selector ).html(
                '<div class="psycho-msg psycho-msg-' + App.esc( type ) + '">' +
                App.esc( msg ) + '</div>'
            );
        },

        /* -----------------------------------------------
           BIND ENTRY FORM
        ----------------------------------------------- */
        bindEntry: function () {
            $( '#psycho-entry-form' ).on( 'submit', function ( e ) {
                e.preventDefault();
                var name    = $.trim( $( '#psycho_name' ).val() );
                var email   = $.trim( $( '#psycho_email' ).val() );
                var phone   = $.trim( $( '#psycho_phone' ).val() );
                var consent = $( '#psycho_consent' ).is( ':checked' );

                if ( ! name || ! email || ! phone ) {
                    App.showMsg( '#psycho-entry-msg', 'Please fill in all fields.', 'error' );
                    return;
                }
                if ( ! consent ) {
                    App.showMsg( '#psycho-entry-msg', 'Please agree to the Privacy Policy.', 'error' );
                    return;
                }

                $( '#psycho-entry-btn .btn-text' ).hide();
                $( '#psycho-entry-btn .btn-loader' ).show();
                $( '#psycho-entry-btn' ).prop( 'disabled', true );

                $.post( PsychoApp.ajax_url, {
                    action: 'psycho_register_participant',
                    nonce:  PsychoApp.nonce,
                    name:   name,
                    email:  email,
                    phone:  phone
                }, function ( res ) {
                    if ( res.success ) {
                        App.session_key = res.data.session_key;
                        App.participant  = { name: res.data.name, email: email };
                        $( '#psycho-welcome-name' ).text( res.data.name );
                        App.showStep( 'tests' );
                    } else {
                        App.showMsg( '#psycho-entry-msg', res.data.msg || PsychoApp.i18n.error_generic, 'error' );
                        $( '#psycho-entry-btn .btn-text' ).show();
                        $( '#psycho-entry-btn .btn-loader' ).hide();
                        $( '#psycho-entry-btn' ).prop( 'disabled', false );
                    }
                } ).fail( function () {
                    App.showMsg( '#psycho-entry-msg', PsychoApp.i18n.error_generic, 'error' );
                    $( '#psycho-entry-btn .btn-text' ).show();
                    $( '#psycho-entry-btn .btn-loader' ).hide();
                    $( '#psycho-entry-btn' ).prop( 'disabled', false );
                } );
            } );
        },

        /* -----------------------------------------------
           BIND TEST LIST
        ----------------------------------------------- */
        bindTestList: function () {
            // Change info
            $( document ).on( 'click', '#psycho-change-info', function () {
                document.cookie = 'psycho_session=; Max-Age=0; path=/';
                App.session_key = '';
                App.participant  = null;
                App.showStep( 'entry' );
            } );

            // Start test click
            $( document ).on( 'click', '.psycho-start-test', function () {
                var $card       = $( this ).closest( '.psycho-test-card' );
                var test_id     = $card.data( 'test-id' );
                var test_name   = $card.data( 'test-name' );
                var has_passkey = $card.data( 'has-passkey' ) === '1' || $card.data( 'has-passkey' ) === 1;
                var hint        = $card.data( 'passkey-hint' );

                App.current_test = { id: test_id, name: test_name };

                if ( has_passkey ) {
                    $( '#psycho-modal-test-name' ).text( test_name );
                    $( '#psycho-passkey-input' ).val( '' );
                    $( '#psycho-passkey-msg' ).empty();
                    if ( hint ) {
                        $( '#psycho-modal-hint' ).text( 'Hint: ' + hint ).show();
                    } else {
                        $( '#psycho-modal-hint' ).hide();
                    }
                    $( '#psycho-passkey-overlay' ).fadeIn( 150 );
                    setTimeout( function () { $( '#psycho-passkey-input' ).focus(); }, 200 );
                } else {
                    App.loadTest( test_id );
                }
            } );

            // Submit passkey
            $( document ).on( 'click', '#psycho-passkey-submit', function () {
                App.verifyPasskey();
            } );

            // Enter key on passkey input
            $( document ).on( 'keydown', '#psycho-passkey-input', function ( e ) {
                if ( e.key === 'Enter' ) App.verifyPasskey();
            } );

            // Cancel modal
            $( document ).on( 'click', '#psycho-passkey-cancel, #psycho-passkey-overlay', function ( e ) {
                if ( e.target === this ) {
                    $( '#psycho-passkey-overlay' ).fadeOut( 150 );
                }
            } );
        },

        /* -----------------------------------------------
           VERIFY PASSKEY
        ----------------------------------------------- */
        verifyPasskey: function () {
            var passkey = $.trim( $( '#psycho-passkey-input' ).val() );
            if ( ! passkey ) {
                App.showMsg( '#psycho-passkey-msg', 'Please enter the passkey.', 'error' );
                $( '#psycho-passkey-input' ).addClass( 'psycho-shake' );
                setTimeout( function () { $( '#psycho-passkey-input' ).removeClass( 'psycho-shake' ); }, 500 );
                return;
            }

            $( '#psycho-passkey-submit' ).prop( 'disabled', true ).text( '...' );

            $.post( PsychoApp.ajax_url, {
                action:  'psycho_verify_passkey',
                nonce:   PsychoApp.nonce,
                test_id: App.current_test.id,
                passkey: passkey
            }, function ( res ) {
                if ( res.success ) {
                    $( '#psycho-passkey-overlay' ).fadeOut( 150 );
                    App.loadTest( App.current_test.id );
                } else {
                    App.showMsg( '#psycho-passkey-msg', res.data.msg || PsychoApp.i18n.error_generic, 'error' );
                    $( '#psycho-passkey-input' ).addClass( 'psycho-shake' );
                    setTimeout( function () { $( '#psycho-passkey-input' ).removeClass( 'psycho-shake' ); }, 500 );
                    $( '#psycho-passkey-submit' ).prop( 'disabled', false ).text( 'Start Test →' );
                }
            } ).fail( function () {
                App.showMsg( '#psycho-passkey-msg', PsychoApp.i18n.error_generic, 'error' );
                $( '#psycho-passkey-submit' ).prop( 'disabled', false ).text( 'Start Test →' );
            } );
        },

        /* -----------------------------------------------
           LOAD TEST
        ----------------------------------------------- */
        loadTest: function ( test_id ) {
            $( '#psycho-questions-container' ).html(
                '<div class="psycho-loading-qs"><span>⏳ ' + PsychoApp.i18n.loading + '</span></div>'
            );
            App.showStep( 'exam' );

            $.post( PsychoApp.ajax_url, {
                action:  'psycho_get_test_data',
                nonce:   PsychoApp.nonce,
                test_id: test_id
            }, function ( res ) {
                if ( res.success ) {
                    App.test_data = res.data;
                    App.current_test = res.data.test;
                    App.renderExam( res.data );
                } else {
                    $( '#psycho-questions-container' ).html(
                        '<div class="psycho-msg psycho-msg-error">' + App.esc( res.data.msg ) + '</div>'
                    );
                }
            } );
        },

        /* -----------------------------------------------
           RENDER EXAM
        ----------------------------------------------- */
        renderExam: function ( data ) {
            var test      = data.test;
            var questions = data.questions;

            // Header
            $( '#psycho-exam-title' ).text( test.title );
            if ( test.category ) {
                $( '#psycho-exam-category' ).text( test.category ).show();
            }

            // Timer
            if ( App.timer_int ) clearInterval( App.timer_int );
            App.time_elapsed = 0;

            if ( test.time_limit && test.time_limit > 0 ) {
                var totalSecs = test.time_limit * 60;
                var remaining = totalSecs;

                $( '#psycho-timer-box' ).show();
                $( '#psycho-timer-display' ).text( App.fmtTime( remaining ) );

                App.timer_int = setInterval( function () {
                    App.time_elapsed++;
                    remaining--;
                    $( '#psycho-timer-display' ).text( App.fmtTime( remaining ) );

                    var $timer = $( '#psycho-timer-box' );
                    $timer.removeClass( 'warning danger' );
                    if ( remaining <= 60 ) {
                        $timer.addClass( 'danger' );
                    } else if ( remaining <= totalSecs * 0.25 ) {
                        $timer.addClass( 'warning' );
                    }

                    if ( remaining <= 0 ) {
                        clearInterval( App.timer_int );
                        App.submitTest( true );
                    }
                }, 1000 );
            } else {
                $( '#psycho-timer-box' ).hide();
                App.timer_int = setInterval( function () {
                    App.time_elapsed++;
                }, 1000 );
            }

            // Build questions HTML
            var html = '';
            $.each( questions, function ( i, q ) {
                html += '<div class="psycho-q-block" data-qid="' + q.id + '">';
                html += '<div class="psycho-q-num">Q' + ( i + 1 ) + '</div>';
                html += '<div class="psycho-q-text">' + App.esc( q.question ) + '</div>';
                html += '<div class="psycho-options">';
                $.each( q.options, function ( j, opt ) {
                    html += '<label class="psycho-option-label">';
                    html += '<input type="radio" name="q_' + q.id + '" value="' + opt.id + '">';
                    html += '<span class="psycho-radio-circle"></span>';
                    html += '<span class="psycho-option-text">' + App.esc( opt.text ) + '</span>';
                    html += '</label>';
                } );
                html += '</div></div>';
            } );

            $( '#psycho-questions-container' ).html( html );
            $( '#psycho-submit-test' ).prop( 'disabled', true );
            App.updateProgress( 0, questions.length );
        },

        /* -----------------------------------------------
           BIND EXAM
        ----------------------------------------------- */
        bindExam: function () {
            $( document ).on( 'change', '#psycho-questions-container input[type="radio"]', function () {
                var $label = $( this ).closest( '.psycho-option-label' );
                var $block = $( this ).closest( '.psycho-q-block' );

                // Mark parent labels
                $block.find( '.psycho-option-label' ).removeClass( 'selected' );
                $label.addClass( 'selected' );
                $block.addClass( 'answered' );

                var total    = App.test_data ? App.test_data.questions.length : 0;
                var answered = $( '#psycho-questions-container .psycho-q-block.answered' ).length;
                App.updateProgress( answered, total );

                if ( answered === total ) {
                    $( '#psycho-submit-test' ).prop( 'disabled', false );
                }
            } );

            $( document ).on( 'click', '#psycho-submit-test', function () {
                App.submitTest( false );
            } );
        },

        /* -----------------------------------------------
           SUBMIT TEST
        ----------------------------------------------- */
        submitTest: function ( auto ) {
            if ( App.timer_int ) clearInterval( App.timer_int );

            var total    = App.test_data ? App.test_data.questions.length : 0;
            var answered = $( '#psycho-questions-container .psycho-q-block.answered' ).length;
            var unanswered = total - answered;

            if ( ! auto && unanswered > 0 ) {
                if ( ! confirm( PsychoApp.i18n.confirm_submit + ' (' + unanswered + ' unanswered)' ) ) {
                    return;
                }
            }

            $( '#psycho-submit-test .btn-text' ).hide();
            $( '#psycho-submit-test .btn-loader' ).show();
            $( '#psycho-submit-test' ).prop( 'disabled', true );

            var responses = {};
            $( '#psycho-questions-container input[type="radio"]:checked' ).each( function () {
                var qid = $( this ).closest( '.psycho-q-block' ).data( 'qid' );
                responses[ qid ] = $( this ).val();
            } );

            $.post( PsychoApp.ajax_url, {
                action:     'psycho_submit_test',
                nonce:      PsychoApp.nonce,
                test_id:    App.current_test.id,
                responses:  responses,
                time_taken: App.time_elapsed
            }, function ( res ) {
                if ( res.success ) {
                    App.showStep( 'results' );
                    App.fetchAndRenderResult( res.data.result_id );
                } else {
                    alert( res.data.msg || PsychoApp.i18n.error_generic );
                    $( '#psycho-submit-test .btn-text' ).show();
                    $( '#psycho-submit-test .btn-loader' ).hide();
                    $( '#psycho-submit-test' ).prop( 'disabled', false );
                }
            } ).fail( function () {
                alert( PsychoApp.i18n.error_generic );
                $( '#psycho-submit-test .btn-text' ).show();
                $( '#psycho-submit-test .btn-loader' ).hide();
                $( '#psycho-submit-test' ).prop( 'disabled', false );
            } );
        },

        /* -----------------------------------------------
           UPDATE PROGRESS
        ----------------------------------------------- */
        updateProgress: function ( answered, total ) {
            var pct = total > 0 ? Math.round( ( answered / total ) * 100 ) : 0;
            $( '#psycho-progress-fill' ).css( 'width', pct + '%' );
            $( '#psycho-answered-count' ).text( answered + ' of ' + total + ' answered' );
            $( '#psycho-progress-pct' ).text( pct + '%' );

            var unanswered = total - answered;
            if ( unanswered > 0 ) {
                $( '#psycho-unanswered-note' )
                    .text( unanswered + ' question(s) unanswered' )
                    .show();
            } else {
                $( '#psycho-unanswered-note' ).hide();
            }
        },

        /* -----------------------------------------------
           FETCH AND RENDER RESULT
        ----------------------------------------------- */
        fetchAndRenderResult: function ( result_id ) {
            $.post( PsychoApp.ajax_url, {
                action:    'psycho_get_result',
                nonce:     PsychoApp.nonce,
                result_id: result_id
            }, function ( res ) {
                if ( res.success ) {
                    App.renderResult( res.data );
                }
            } );
        },

        /* -----------------------------------------------
           RENDER RESULT
        ----------------------------------------------- */
        renderResult: function ( d ) {
            // Basic info
            $( '#psycho-result-emoji' ).text( d.result_icon || '🏆' );
            $( '#psycho-result-participant-name' ).text( d.participant_name );
            $( '#psycho-result-test-name' ).text( d.test_title );
            $( '#psycho-result-label' )
                .text( d.result_label )
                .css( { background: d.result_color, color: '#fff' } );
            $( '#psycho-result-desc' ).text( d.result_desc );

            // Recommendation
            if ( d.recommendation ) {
                $( '#psycho-recommendation-text' ).text( d.recommendation );
                $( '#psycho-recommendation-box' ).show();
            } else {
                $( '#psycho-recommendation-box' ).hide();
            }

            // PDF button
            if ( d.pdf_url ) {
                $( '#psycho-download-report' ).attr( 'href', d.pdf_url );
            } else {
                $( '#psycho-download-report' ).attr( 'href', PsychoApp.result_base + d.result_id );
            }

            // Animate SVG ring
            var circumference = 326.7;
            var pct = d.max_score > 0 ? d.total_score / d.max_score : 0;
            var offset = circumference - ( pct * circumference );
            var ringColor = d.result_color || PsychoApp.brand_color;
            $( '#psycho-ring-fill' ).css( 'stroke', ringColor );
            setTimeout( function () {
                $( '#psycho-ring-fill' ).css( 'stroke-dashoffset', offset );
            }, 300 );

            // Count-up score
            $( { val: 0 } ).animate( { val: d.total_score }, {
                duration: 1500,
                step: function () {
                    $( '#psycho-score-num' ).text( Math.ceil( this.val ) );
                },
                complete: function () {
                    $( '#psycho-score-num' ).text( d.total_score );
                }
            } );

            // Trait bars + chart
            var traits = d.trait_scores;
            if ( traits && Object.keys( traits ).length > 0 ) {
                var traitNames  = Object.keys( traits );
                var traitValues = Object.values( traits );
                var maxVal      = Math.max.apply( null, traitValues ) || 1;

                var barsHtml = '';
                $.each( traitNames, function ( i, name ) {
                    var val = traitValues[ i ];
                    var pctBar = Math.round( ( val / maxVal ) * 100 );
                    barsHtml += '<div class="psycho-trait-row">';
                    barsHtml += '<div class="psycho-trait-name"><span>' + App.esc( name ) + '</span><span>' + val + ' pts</span></div>';
                    barsHtml += '<div class="psycho-trait-bar-bg"><div class="psycho-trait-bar-fill" data-pct="' + pctBar + '" style="width:0%"></div></div>';
                    barsHtml += '</div>';
                } );

                $( '#psycho-trait-bars' ).html( barsHtml );
                $( '#psycho-traits-card' ).show();

                // Animate bars after a moment
                setTimeout( function () {
                    $( '.psycho-trait-bar-fill' ).each( function () {
                        $( this ).css( 'width', $( this ).data( 'pct' ) + '%' );
                    } );
                }, 400 );

                // Radar chart
                if ( App.chart_inst ) {
                    App.chart_inst.destroy();
                    App.chart_inst = null;
                }

                var ctx = document.getElementById( 'psycho-radar-chart' );
                if ( ctx && typeof Chart !== 'undefined' ) {
                    App.chart_inst = new Chart( ctx, {
                        type: 'radar',
                        data: {
                            labels: traitNames,
                            datasets: [ {
                                label: d.test_title,
                                data:  traitValues,
                                backgroundColor: 'rgba(108,99,255,.15)',
                                borderColor:     PsychoApp.brand_color,
                                pointBackgroundColor: PsychoApp.brand_color,
                                borderWidth: 2,
                            } ]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                r: {
                                    beginAtZero: true,
                                    ticks: { display: false },
                                    grid: { color: 'rgba(0,0,0,.07)' },
                                    angleLines: { color: 'rgba(0,0,0,.07)' },
                                    pointLabels: { font: { size: 12, weight: '600' } }
                                }
                            }
                        }
                    } );
                }
            } else {
                $( '#psycho-traits-card' ).hide();
            }

            // Confetti
            App.launchConfetti();
        },

        /* -----------------------------------------------
           BIND RESULTS
        ----------------------------------------------- */
        bindResults: function () {
            $( document ).on( 'click', '#psycho-retake-btn', function () {
                App.showStep( 'tests' );
            } );
        },

        /* -----------------------------------------------
           LAUNCH CONFETTI
        ----------------------------------------------- */
        launchConfetti: function () {
            var colors = [ '#6c63ff', '#f50057', '#00c853', '#ff9800', '#1a1a2e', '#fff' ];
            var wrap   = $( '#psycho-confetti-wrap' );
            wrap.empty();

            for ( var i = 0; i < 70; i++ ) {
                ( function () {
                    var size   = Math.random() * 10 + 6;
                    var color  = colors[ Math.floor( Math.random() * colors.length ) ];
                    var left   = Math.random() * 100;
                    var delay  = Math.random() * 2;
                    var dur    = Math.random() * 2 + 2;
                    var $piece = $( '<div class="psycho-confetti-piece"></div>' ).css( {
                        left:             left + '%',
                        width:            size + 'px',
                        height:           size + 'px',
                        background:       color,
                        'animation-duration':  dur + 's',
                        'animation-delay':     delay + 's',
                        'border-radius':       Math.random() > 0.5 ? '50%' : '2px',
                    } );
                    wrap.append( $piece );
                } )();
            }

            setTimeout( function () { wrap.empty(); }, 5000 );
        },

        /* -----------------------------------------------
           HELPERS
        ----------------------------------------------- */
        fmtTime: function ( secs ) {
            if ( secs < 0 ) secs = 0;
            var m = Math.floor( secs / 60 );
            var s = secs % 60;
            return ( m < 10 ? '0' : '' ) + m + ':' + ( s < 10 ? '0' : '' ) + s;
        },

        esc: function ( str ) {
            return $( '<div>' ).text( String( str || '' ) ).html();
        }
    };

    $( document ).ready( function () {
        if ( $( '#psycho-portal' ).length ) {
            App.init();
        }
    } );

}( jQuery ) );
