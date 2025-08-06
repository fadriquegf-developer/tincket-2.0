@extends('brands.teatresdespi.emails.layout')

@section('content')
    {!! $mailing->content !!}
@endsection

@section('extra_content')
    @include('core.emails.mailing.embedded_extra_content')
@endsection

@section('footer')
    {{-- <td colspan="2" rowspan="1" style="border-top:1px solid black; font-size:12px; padding-top:10px;">
                        <b>Informació sobre el tractament de dades personals</b><br>
                        En virtut Reglament (UE) 2016/679 del Parlament Europeu i del Consell, de 27 d’ abril de 2016, relatiu a la protecció de les persones físiques pel que fa al tractament de dades personals i a la lliure circulació d’aquestes dades i pel qual es deroga la Directiva 95/46/CE (Reglament general de protecció de dades), i LO 3/2018, de 5 de desembre (LOPDGDD) informem del tractament de dades personals ens el termes que s’exposen a continuació em dono per assabentat/da i autoritzo a què:<br>
                        Les dades de caràcter personal facilitades seran responsabilitat de L’Ajuntament de Sant Joan Despí.<br>
                        Les dades que se sol·liciten resulten necessàries, de manera que de no facilitar-les no serà possible la prestació del servei requerit, en aquest sentit, consento expressament la recollida i el tractament de les mateixes per a la esmentada finalitat. No és realitzaran cessions al marge de les obligades per llei.<br>
                        <b>Drets:</b> Les persones sol·licitants poden revocar el consentiment, exercir els drets d'accés, rectificació, limitació, supressió, oposició o portabilitat, adreçant-se per escrit al registre del Camí del Mig, 9 – 11, 08970 Sant Joan Despí (Barcelona) o al Delegat de Protecció de Dades dpd@sjdespi.net.  Cal adjuntar-hi fotocòpia del seu document oficial d’identificació, o telemàticament mitjançant la seu electrònica de l’Ajuntament <a href="https://www.seu-e.cat/web/santjoandespi/seu-electronica" target="_blank">https://www.seu-e.cat/web/santjoandespi/seu-electronica</a><br>
                        <b>Delegat de Protecció de Dades:</b>  <a href="mailto:dpd@sjdespi.net">dpd@sjdespi.net</a><br>
                        <b>Finalitat:</b> Gestió del servei de newsletter de Teatres Despí<br>
                        <b>Legitimació:</b>  El consentiment atorgat pel tractament per aquesta finalitat<br>
                        <b>Conservació:</b> Les dades es conservaran durant el termini estrictament necessari per complir amb la finalitat esmentada, respectant en tot cas el que determini la normativa d’arxivament aplicable o fins que revoqui el seu consentiment en aquells casos que sigui la causa que legitima el tractament.<br> 
                        <b>Destinataris:</b> El Responsable podrà comunicar a les autoritats competents en la matèria<br>
                        <b>Reclamació:</b> Pot presentar una reclamació davant l’Autoritat Catalana de Protecció de Dades (apdcat.gencat.cat). <br>
                        <b>Informació adicional ampliada</b> a “Política de Protecció de Dades” al web  <a href="www.sjdespi.cat" target="_blank">www.sjdespi.cat</a> – Protecció de dades
                    </td> --}}
@endsection
