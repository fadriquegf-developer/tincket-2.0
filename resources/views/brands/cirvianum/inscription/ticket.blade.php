<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="manifest" href="site.webmanifest">
        <link rel="apple-touch-icon" href="icon.png">
        <!-- Place favicon.ico in the root directory -->

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">

        <style>
            body {
            }
            h1 {
                font-size: 2em;
                max-height: 50px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                text-transform: uppercase;
            }
            img.logo {
                width: 100%;
            }
            .event p {
                margin-bottom: 0;
                line-height: 1.5em;
                line-height: 1em;
                font-size: 16px;
            }
            .conditions {
                bottom: 0;
            }
            .conditions p {
                margin-top: 0;
                margin-bottom: 0;
                line-height: 1em;
                font-size: 10px;
            }
            .confirmation-code {
                color: red;
                font-weight: 300;
                float: right;
            }
            .bar-code {
                font-size: 0.5em;
                display: block;
                margin: 10px 0;
            }
            .bar-code span {
                display: block;
                right: 0;
            }
        </style>

    </head>
    <body>
        <!--[if lte IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
        <![endif]-->
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <div class="row">
                        <div class="col-xs-4">
                            <img class="logo" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAV8AAADQCAYAAACgJGI4AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAOxAAADsQBlSsOGwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAACAASURBVHic7Z15mCRFtfbfkxkRWT3NKjDs68Cwb7IzgKzKAIKgooAogqCfOyqX7Sr3uqCC4q4oiGyCCIoKDKAjIIt62QYUEBiQfdiHkWGmKyMz43x/ZHXTXZ1VmdVd1dlddX7PM/N0ZUZGvrmdjDwRcQ5BECYX2hizcQJsRsB6TLQeMa8DYKVh/wjA8gA8AIvBvJSJlhCwCERLwLyYmZ/wPG++Ax5VwPwwDJ8EEJd2VIJQB5UtQOhtgiCY6Zzb1XnersS8A4DNAJgO7CoCMJ+JbgfzrYnv34KBgec6sB9BKIQYX2GiqfjGvANEsz3m2QysU5YQBh4HcCuA6xNrrwUwUJYWofcQ4ytMCFrr7Rj4IDzvSDCvXLaeDAYIuNYRXZKE4Q1IW8qC0DHE+Aqdo1JZTzn3IQaOJmBG2XIKQ/QyO3eF8rwfhGH4aNlyhO5EjK/QdowxmzNwMgNHAFBl6xkHDsAcOPf1OI7/WrYYobsQ4yu0Da31dkx0GoB3IR2J0D0Q3cbA2UkYXguAy5YjTH3E+ArjxhizuQPOBjC7bC0dh+g2cu6zURTdW7YUYWojxlcYD9OUMf8F4FR0ZnjYZMUR8MtI65OwZMmLZYsRpiZ+2QKEqYlvzGHK968FcDB67z4iAFt7zh3vKWVdktwJcUUILSItX6E1pk1bQ8Xx+egFF0NRiG6PiY5Gtfpk2VKEqUOvtViEcaAqlb29JLkRwLZla5lkrOMxH+t73kLn3D1lixGmBmJ8hSIoZcyXwHw+gGXLFjNJCUD0Tt/3N3dJMhdAtWxBwuRG3A5Cc/r61lLOXQbm3cuWMlVg4HFFdGAYho+UrUWYvIjxFRqitX4rA3NAtGrZWqYgr8K5Q+M4vq1sIcLkRNwOQiaqUtkTwPUgWqlsLVOUaSA6ijzvKXbuH2WLESYfYnyFUfjGvIeYfwugv2wtUxyfiN7l+b5zSXJr2WKEyYUYX2EEWusPg+gi9NakiU5CAPbyfJ9cktxSthhh8iDGVxhCa/1RJjoP3RaXYXKwp+f7kUsS8QELAMT4CjV8Yw6ptXjF8HaOfTzfH3BJckfZQoTyEeMrQFUqexPz7wDosrX0APv6nveSc+7usoUI5SJDzXocY8xWDvgLgBXK1tJDOCY6KAnD68sWIpSHGN9epq9vLRXHd8s43lJY6BPtGIbh42ULEcpB/Hu9i1LOXSaGtzTekjBfA2C5soUI5SDGt0dRxnxFpgyXzqbKmIsgX6A9iXS49SAqCPYH8GPIQz8Z2MRXapFLkr+XLUSYWOTh6zX6+1dTUTQPwGplSxGGWOoTbROG4fyyhQgTh7gdegwVRb+AGN7JxrQEuADyPPYU4nboIXxj3kPAaWXrEDJZR9wPvYW4HXqHZbQxDzGwdtlChIYs9Ym2DsPwsbKFCJ1HPnN6BGXMGWJ4Jz3TEuZvlS1CmBik5dsDGGM2d8A8yPThqYFzu8dxfHvZMoTOIi3fHsAB34IY3qmD73+jbAlC5xHj2+VorbcF8I6ydQgtwDzLN+aQsmUInUWMb5fDRF+EuJemHAScCRmN1NWI8e1ijDGbApAW1NRkM2n9djdifLsYBv4bco2nLMT8ybI1CJ1DPke7lEqlsn7s3HzIp+tUhj1gS2vtg2ULEdqPtIq6lNi5D0EM71SHEqJPlC1C6AzS8u1OyDdmPgEzyhYijJulsbVrAXitbCFCe5GWbxeilNpNDG/XME1rfXjZIoT2I8a3C2HfP7psDUL7YKJDy9YgtB9xO3QfFWXM85CEmN1EFFu7KsT10FVIy7fL8I3ZH2J4uw3tG3NA2SKE9iLGt9sgkoe0CyFAXA9dhrgdugxtzNMSOrIrWRJbuzKAatlChPYgLd8uIgiCmWJ4u5Z+pdRbyxYhtA8xvl2Ec263sjUInYN8f6eyNQjtQ4xvF+E8b9eyNQgdhHnHsiUI7UOVLUBoH8S8Q9kaOsxCAq4D0f855mfJuVfZ95fznFvVed4uYN6HgA3KFtkpHCAt3y5COty6B6OMWQzAlC2kAzzMwBcTa38HIG5SjlQQvAPAqWDeY4K0TSixtdMBvFy2DmH8iNuhSzDGbILuM7wJgFNia7dMrL0KzQ0vAHAchjfEYbgnMZ8AYHHnJU4sKgi2K1uD0B7E+HYJCbBJ2RrazGImOji29pvIN7r1cBRF5xHzXiDqqlYiObd+2RqE9iDGt0sgoJseyoSJjkjCcM54Komi6B6PeV8AS9ukq3SYSIYSdgnS4dYlMNF6xFy2jHbxxSQMr6v9vZwfBGe3WoECzgrD8HFr7T98rT9GRBe3WWMpkIzj7hqkw61LUMZcA+CgsnWMFwYeS6zdDEAEAOjvX01F0fMtV+TcbnEc3zH4U2l9M4j2bJfO0iD6SxyGe5YtQxg/4nboHlYuW0Cb+BIGDW89zHPh3KzY2uUALBpcHPv+WsS8LQG/algr8+ntFloGzLxO2RqE9iBuhy6BgZW74DNmcWLtbxusezSOogOQZZgHBgYi4D4ARyitp4No7/oicRz/VRnzKICZbVU8wRCwetkahPYgLd8ugYimfBhJAm4AEGatY6Jb0ahFPKISurnJ2p+PTdmkYn7ZAoT2IC3fboE5KFvCeGGiOxutI+bnCtXB/DRR9jdAbO13tNYLmWilMUosFWJ+KTJmDqwtW4rQBsT4dg9TfoIFZxnYJUsWolLZTwH/LjLYN9H6T8q5/eLsdOtRFEXnj1tomUT5jX9haiDGt3vQZQsYL0S0MGOxjavVuYVnWSxd+nwMtD46QhAmGPH5dg+tzgKbdDDzlHQHCMJYkJZv9xBiirseiKjTPfm+r/VRQ/shism5KRP/wREtqQUXWlK2FmH8iPHtFohCMC9btozx0OmQmCoIPgPmbw8tYAY36JybjBAAZcxvY2vfXbYWYfyI26FLYOZF+aUmNwzMBtC5URvMx3es7onj7WULENqDGN8ugYBXy9bQBpbzjTmkQLmVAEwb/GGMWStvA631zuiCyG8MvFK2BqE9iPHtHrrioSTgK2gwckMHwSeVMQuVMS9gmH/bAfNqyxsG4GHga+1XO/EQ0dNlaxDag/h8uwQmerZLoprNVMacGFt7Vv0KBzzlAXMbbsn8SNZi35gjAYyacjwVIeZnytYgtAcxvl0CMT9RtoY2cqYKgvvjMLxx+MIkDK9JgGtaqcgYs6UDftZeeeXBgLR8uwRxO3QJDDxZtoY24oP5ChUE+42nEq31to55LoD+NukqHWn5dg9ifLsEH/hX2RrazPJgnqOC4HMA/FY31lofw0S3gGh6B7SVhvO8J8vWILSHqTPIUcijm7MXP8hEp9WyWyTNCqpKZV8kyalZYSW7gdiYVfHGGy+VrUMYP2J8uwhlzD8AbFm2jo5B9DIzX+Mx/8153vOecy+y76/Azq0Gol2IeT8AG5Uts1Mw8GRibTfl6utppMOti2DgLupm48u8CgHHMtGxNDg7zbm0BdEdIz2a4gF/b9rsF6YU4vPtIjzmO/JLCVOWJvGOhamHGN8uwvM8Mb5dDCeJGN8uQny+XYY25mmW9OLdyJLY2lUADJQtRGgP0vLtMhzRjfmlhCnIDRDD21WI8e02mOeULUFoPwz8rmwNQnsRt0P3MU0Z8yKAZcoWIrSNKLZ2VQCvlS1EaB/S8u0+lhJwXdkihLZyE8Twdh1ifLsQB/y6bA1C+yBmcTl0IeJ26E6M0vqZbotr0KMsja1dC9Ly7Tqk5dudWBBdVLYIYfww0cUQw9uVSMu3SwmCYMOE+WGMISKYMHnwgK2stf8sW4fQfqTl26WEYfgYAVeVrUMYB8y3iOHtXsT4djG1fGiubB3C2GCiH5atQegcYny7GGvtgwCuLVuHMCYeSaz9fdkihM4hxrfLIeauyNrbazBwKoC4bB1C55AOtx5Aaf1HEI0rH5owofwttnYWgO4PUtzDSMu3B/CIPgcgKluHUBDPOx1ieLseGYbUAyRJ8pLneSuCaJeytQi5/CEOw6+XLULoPOJ26B2WVcb8C8CaZQsRGjLgE20ThuGjZQsROo+4HXqHxQx8vmwRQmOI6HQxvL2DtHx7DOl8m7T8NbZ2d8i47J5BjG+v0de3lnJuHphXLluKMEToAW+11j5UthBh4hC3Q68xMPAsA8dAetMnD0Sni+HtPWS0Qw/CSTLf8/1+ALPK1iLg97G1J0Jehj2HuB16F6WC4BYwiwEuj4dja3cG8J+yhQgTj7gdepc49rwjQfRy2UJ6lEU+0cEQw9uziPHtZQYGnibnDgCwuGwpPYZjoqPCMJxfthChPMT49jhRFN0NoncDsGVr6RWI6DNJGM4pW4dQLtLhJsAlyb/J9x8j4DBIP0CnOS229pyyRQjlI8ZXAABwkjzge97LIDqwbC1dzBmxtRLiUwAgxlcYhnPubl+pVwHsD2kBtxfmc+IoOr1sGcLkQYyvMAKXJHeS788n4J2Q+6NdfC2OolPKFiFMLqR1I2SiKpW94dzVAJYrW8sUJibmT0VRdG7ZQoTJhxhfoSFa6+0ZuA5E08vWMgV5DZ737rhavblsIcLkRIaaCQ2JoujuWKntQXRH2VqmEgz82wNmieEVmiE+PaE5cfy6S5KLPd9nAHtAvpby+E1i7SFJkjxdthBhciMPklAYVansiyS5FESrlq1lErKYmL8QRdHPyhYiTA3E+Aqt0de3torjC0C0b9lSJhF/94k+EIbh42ULEaYO4vMVWmNg4Jk4ivZj4P0AFpQtp2QWAzg5tnZ3MbxCq4jPVxgTnCQPuiT5qef7EYBd0Vv3EhNwaaz1wa5avRGS+kcYA+J2EMaNMWYrx/ztHnFF/I2YPxNF0V1lCxGmNmJ8hbahtd6ZiU4DcBC67976OwNnJdb+DpJ1QmgD3faACJMAY8yWDJzEwJGY2u4IBvOf2fO+n4ThNWWLEboLMb5CxwiCYKOE+RgCPsDAOmXraYHXmOgqn/l71toHyxYjdCdifIWJwFNK7cq+fzQxvx+TM15EFcBcBi5OrP09JLi80GHE+AoTTZ9vzAEgmk3M+wNYsywhBDwN4C8OuCGx9g8A3ihLi9B7iPEVSsUYsykzzwLRbgzsCGAjAKoDu3IM/BvA7WC+Rfv+rdVq9YkO7EcQCiHGV5hsBFrrzR3RpgSsz0TrEfNaAKYTsBIDywJYAaPv3UVIW66vgvll9rwnCJjPzPN94FFr7eMAwgk+FkEQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBEEQBEGYaoGufa31jqT1W5XnLe+cexGSVaAsfK319qT1dsrzVnTOvQC5FoLQffjGHKaMeUYZw4P/tDFP+kHwzrK19Rp+ELxTG/Pk8GuhjHnGN+bdZWsTBKGNaK0/ooxxdQ/74D/na3102Rp7BV/ro5UxSaNrobU+oWyNgjAVmPwhJfv61lZJ8iiASpNSS2KtN8SSJS9MlKyepL9/NRVFjwHob1KqGvv+xhgYeHqiZAnCVMQrW0AeKkmOQHPDCwD9Oo4Pnwg9vUztHDczvABQqV0zQRCaMOmNLxPNLFLOAZt0Wkuv45wrdC1YroUg5DLpjS+cqxYpRs4t7bSUXofSJJMFCpJcC0HIYfIbX6LbixTjguWEsVP4HDPLtRCEHCZ/hxuglTHzAGzepMy82NodACQTpKlX8ZUxdwHYtkmZh2JrtwEQTZAmQZiSTP6WLxD5RO9iIDPTLAOPxZ53GMTwTgRJ7HmHApiftZKBJ3yid0EMryDkMiVmuCVJspCT5GLP92MAKwLQSA3ATxNrP4w4frFchT1EHP/HpdfC4s1r8RiA8xNrj0mS5LlyBQqCIAiCIAiCIAiCIAhCTzOm0Q5BEGwUM+9AwEwmWg0AwTlLRK8R0aucJPfFcXwPgDfaK3dSsqxvzGwCtmei5eFcSETPw7k74jj+G6Tzabx4xpiNE2Azj2gtZl6diVasrWNiXgTgdWZ+xieaZ619GEBcot4i9KlKZScwvxXOrcGetyyci4joFQYeSaydC+DlskVOVSqVygZRkswiok2Z6C0AiJx7g4D5RPRXa+0/Wq60r29NP0l2JWALZl4JnqfJucXkeU8Q883W2odarbKw8Q2CYGbCfDwB72Ng7QKbJADuJaJLozC8DMArRfajjPki8qewjoKBuxNrr2pad6WyJ5zbv9W6a/Xfl1j7q2GLSAXBiWD+EoDlG2y2AMD3Ymu/g3ojnMZJ+OxYtAyHiJ6NwvCHg7+11h9moo3HWl9s7dcB/AcAlDGnovGxNYSB+xNrLx+rBgDTfGMOJeBIALsBWK6FbQcAXMdEFyRh+EeMbRQMKWO+PobtAAAecKm19oH65bVGy6kEvBvNj8kBmEPMX4qiaF5WAWPMJg44Ziz6GLgzsfa3WetUpbIXnHvHWOoFAJ/ogjAMH80tZ8xRBGw5ln0w8I/E2svqFpNvzHsJOBHAzjlVPMzA12p1uKY6g+BAAk4B865oPjrsIQbOrN33TescEpxXoGZ0vwHgkJydN8OC+UdxFP0vag92I1QQvAzmlVvdAQO/SKw9tmndxpwCYEwPFQGXRdYeVfvp+cacT8CHC25+V2ztbACvDi4wxmzpgNbfwNl17zj4QxkzB8DssVYW+/46GBh4plbXMwDWarUOAn4VWTuW+A5KB8HHmPl/AbxlDNvXc58HfNBa+88Wt/OUMWMeusjAexJrfzNskVHGfAWpYdAtVJUQ0UlRGH6nfoUfBAcR8zVj0kd0XhKGmdHnlDGnAfjaWOoFABDtH4fhjXnFtDG/ZuC9Y9oFcEVk7fuHFvT1ramc+yWY39ZiVXNia49Etk1aRRlzAYCDWqzz+tjaozHsWW9EM2PqK2NOT5j/CeDQnLJ5GBCdqIx5WFUq+46jnskA+UHwkxYMLwDsoIy5DoDqlKguYGVlzB3M/AO0x/ACwDYOuMs35v35RTtEf/+qypjbAfwXWjO8AOAz8zk1gyhkYIzZXMXxPWMwvABwgNL6t6h7Lo0xWyhj7kXrhhcAZitj/gxgmbyCjYzB8krrKwHsN4adN2M1OHedb8xxibWXtrnuCUEZczKYh7calhLwOwYeJuYXaj6mHQAchpFfFjvpIPhEFIbfm1DBU4FllpmurL0JGbMYCXjaAXM95jud5z1LSbKYfX95z7mVmWg9EO0D5llNag8IuFgFwWtFWmRtpa9vbT+KbgKw4Thr+l+l1K1xHMu07eH09a3tkmQuiFYdcx1EeytjvhBb+w0A0Fpv64A/Ix3DPla21sacP6J1nkGW8V1RGXMTgG1ydsAAbiTmKwHcG2n9Epg9HccrOaLNQPQ2Yj4Co31bhoALVaWyIK5Wbyp8OJMApdQuAL5S++kAnB1b+00Arw2WMcZs6YgSMG8BYITvlZk/DkCMbx3K2vMw2vA+xESnxGE4B0AywgcQx8MduWdorbdjoosy6hhEw7mLAMwE8HrbhDdnRZUk1yPb8C4FMK2FuhR8/6uI4z3boqw78JVzlwBYLWPdGyjQ8hzGKQB+EgTBKolzNyDb8IZIG1OmSIUMvE8p9cNmL8x6V0Kl5jPMM7wvwLm9YmtnR1F0QRRF92Hp0gUYGHg2iqL7E2svT8LwY7G1M5jo5xnb+3DulwBG+XZjou2V581QnjfDAzZn4F0A7s472CLE1p47WHet/k1BNBvMN+dty8DK5HmXI31hVZnooNjaU/Cm4Z2mjbnQAfeB+WzUGd4aM4Mg2BAArLWPEPM2cG4PIvoUgKK9pUxEJ/pEGyvPmxH7/qEjjlHrY0ccH/CtnPocgN8T87FwbncMDDw/VJfvzxpW12ZM9E4Afy+osxDGmK0AHFy3OARwtefc2pVKZd28OqIouie2dk8GnmxYiGjVms+/CG74ccO5PRh4HwG/RME8dcqYS/Dmy8AR8Et43j6xtX2xtf2xtct5wBYg+jyI8kc2ML+tdq4AAEkY3lR3L2/OqXvw3oLHmEls7Y/r6t0MRPszcAEKdiQVIdL6Uxn6DwNwX5Hta1+gg64GJuDXINo/tnbZ2j+fmLcj4CLkX7PldRB8KGG+GkTThy1/gJg/Giu1RmxtJbY28Ik2AfANpPdoczzv80WOBQDgB8G5DdLDDP/3APr6WumEIW3MJQ3q+mKhGqZNW0MZY5vp8lPn+FhZVhmzsMCxp2mLjBkVuN0PgvMLbV+p7N1Aw/LKmGcL1HFXi8dGyphXGtRVVUHQas/2KsqYpc00amMKj3RQxpycc7yxCoJCN7HW+ricup7BOINJ+UHw49xrFAR/Gfb7Ea11s0BEQH//ar4xjxW49ifnCuzrW1sZEzV9VoLgZ2M5dqX1twsc+5hHSgAAKpV1VeM0VUPndJg9eF4ptUdT3cacWeDcxsP+TlT6om7YR6O13lEZ83pOnYsBBI3qGGr5qiB4OzF/NOfUvO4THYaBgWdzygFph90XldbPM/CBBmU+hiIdeUuXLmDgqQL7HCuLQTRqaFAWBFycWPvrusV9xHxU5gZ1MHOjz6H/APhhg3XDaXXoFwHoa7Du7DH4QV9GGsuhLTDRjJwiPpi/GQRBbiD3SOvrc4qspbXeuri60RBR5hCtETAPGoO7Ymt3ajRcbIglS14g544rsPu8IVTAwMAzDBR5PluGgCs7Ue8IqtWnCMiLDzITaeflCz7R7nEc39qscGztVwEszKlzMM4NM3BUzQfccLx4FEV3AvhqTp3LaK0bRmMcNHw+0l7mphDzSUXG8AGAHwQ/APDlHGf4GujrW7NIfTTMr9oRmIvUPxApdWr9wkqlshryUx0BADznGt5Yse/njjsEsCH6+wt3MGitt0K2f9HG1o7N/0zUtmvhMRfxofnOufze7KVLX0DO+XNE6xeUli2EuWhuuudiYw4CsKhI4TiO/wIgrwGwaZG6OvWs+L7/UifqrYeLnTMHzzsyDMMiDYGlAG4puPsz68bzN8Qn+l1eGed5DZ9VDwB8rY9E+jZpCAFPRVH0iyKiMG3a6jRyREBDVBRtUKjOSQABV2Pp0ufrl1er1UUo5gtcGEVR47G9AwNPg+iOPBm+tW8vsC8AQM1Pm8UcFJz40kkahQodVc7zli1QrB85X1Iec1YHTWFCpZqOUx+Egc/ijTdaMlZMdFvzArxCK/V1MwT8Kq5Wc/tqhij20lwQW1t4jHMYhqNsQcZ+V2q0ygMAIvpkbh3AeSg4VVY5tzmKh6ss1IExGXBE9bNqBnkNwP8VqOJHyDmH5FyjfQzhER1YYF+DHJK1kIHc/UwEHvAbFLgHuMDDo4Jgl/wdeg19cO2CgcfqJlkUgpibf24TifEdhPmslsoT5bkdgNTGDbRQa34HJHPDxoBnjNkUwI6NCgxCzDcUVcRJsnrRonEc/6tovSXDSRj+tdFKYv4kgMVNtr+7NnW3KVEU/RqAbSoEOBAF3By1kRXbZax6PbH22rztJwJr7QPM/KOmhYheTqz9Y05VPoBJMRmBCr5QRm1HlNeqLjTMqdth4LEoiu5vZRsiyh1iSMzXjV1V63hMVOQT9vXcToNhEHOhT0kw34QpEkCk9nnc0JcWRdE9XvoS+wNGOurfYKKfxNbui2Jv1YUA/pRTZhk/CHJnCiYNOgEZuKqglgkhiaLPgvm7yI7DMI+cOwjNx+euoI0Zy/TSjkDMY+2QnDJfgWVC7ZmWP4ooigr1Z7ULVQsYkcd8tDDGL47jO1QQ3Abm3ZsVI2BU59VkhYBc/10totYhAPqNMesRURyG4RPIacnWw8BllLZumxTiQwHktV5HDYkDAPK8X7aiZwJI4ig6EZXKd3WS7M1EqzDzCx7wQBRFzcatGt+Ywwn4JgNrTJjaHFwbOySF0TBRJ/oqEuTEnWk3ioFN8goR8O8W6+U4DA9VWl8OoqwpypaZT4ijqNUxq2XSyoVZYq19cKw7Sqz9nTKm6SwdSo38R9FgOIzWehsGNstYtSCuVv8yVm0dpVp9KgKGOnUbRLbxtNY7cJor7hhkz3ASuptO5Gts2wSSoigUiFrliAoNl6nj1TiK3q4qlb2RJAeAaAaY+wE86BGdZ6Oo5fiXJTORn4RLCfgDpyEVG7GSqlR2b9Tjy0SNoor9ElMr2aintd4anrc7mGcx0V7MvErZogRhvCgUmANNzo3ZP1iL3zClYjhMBhzR5cTczPiCk+RQAFnGlxg4PGsqFzFPNpdDFitorQ9notkA3sbAiuDau4+z34EEPAWi7zLzNyEdU8IUwEORYT6eV2gCgdA+kjC8ETm+LSKqj5wGIA0ARMB6GZs81Gov8UQSBMHM2lT0BUz0U6RxPfKiS93LwJGRtRtGYfhdSKeVMEXwUGA2jMfcSiYBoT1EtVEJzVhTaz1qmGATl8Ml45fVEaYpY85KmP9Zm4reaDr0IAzgBnjevrG129WyB0z21EGCMAIPBabyMbDOBGgR6qAkyQ1Qw0SH1i3yKTtDAMeeV2ja5ATzFmXMLQBOQr67ICHgEg/YKrZ2dlyt/rnj6gShQxRq+YJoo/HuSBlzqm/M43X/HoP45xoSx/FtTcMkAuA0H9gQqlLZKzOeBtGtqFab1lUCvgqC3yMNPt8cojs8YJvI2g9m5UcTOo9zrpUYxEIOioBnC8ztXCUIgplFg+o0YBcC6uM4PIcWx8D2GGmc0jQFTSYEbGiM2WLQILFzR2R2tKXxkycVOgg+wcy75RZk/nZs7cnIH6Vh0CSEXxbKmC9hWOAhBuYl1l7RSh29QkK0LjXo8BRax2OgUHJB59x4cq/5IBo9756o1fHDPQcx58ZgcGkQaiDNEvKujCI2iqL8UIgTjGP+VG4h5pvjKPoCigyP6+sbSzqZTwM4efCfN7a8XT0BvRkqU2gDHjyvYbyC4TDRR8a6E6XUblkZiRl4ZKx19gq10Ql5n9mHAoBvzGxkJ5+8DgWyqU4olcq6VCC3GbXQSegnSb77Ih9p2mXjM/CeskV0E15crd6ONOdRHtuOOUq9530hczlzXgwDQlKfjQAAEplJREFUISWvo2ybIAhmeEBmwj5OJ1ZMKhRzodi0zNyKq6uVaG9DUob/cERLxlDHlMJjbnnoqG/MYRluQ2EceABCAq4uUpiYf4oWs3rWDHbWQxEXiFQlAIg971LktMgS5qM4+5P59cTaOZ1RNnaYuVBGDiIqOoRsFWrw8mlWPeomGRHzRCXY7Bw5LxBuPZvycgScMw5FQgYeALBz5xYpzMC6Ks3PVSgzaKVS2QDMFyIrb1aatLKVactNx356+WNDm8PcfHui8dU/HqrVpwD8LafU6ci4Lkx0JdodwSznXHGRa0FUSJMjKjTMURtzDlrLCAykwdfr4043d88kSZH7YEyTkjhfP6HIMTLnxa7doVKpFG3FGmXMFSgQhiDdNfcXrLdxHXn3T96zmrlJ7jY+0tRErZCro5ld8gAgjuO/AijaCn2HMua2wSy8jVBKzYqduw2NAp/4/pkF9wekB9A0/QsXTLHSAI+IsrIND9sBz0TxAPFth4jyXA+ZQ/aI6NI2SzHIbzltjJxElZQkhUKJetkdiCNQxpzSJE/gCJh5aDSE1nqLUetzcvn5BdwlVCBYVeZ2zHlZw6GUys9Bx/xkXjVxkvwUTRJEAgCWWWa6MuYPAPbP3WcNArYsWrbRXilnXgGN4VkvsI1njGluA+pQSuXqaBa4bCjKuk/0aQDVgvvdJmF+UBtzmW/MUVrrbSuVygbGmK18Y45QxvwGnncbGoX5I/pLXK3eUnBfK9TyweW9UbdWxpyKFocaAehXxny9wESS1ZXWZ6Fgq7/dRGH4K7Q+i+u5uFptnpqmNZbzg+C7yHc9baKMOQNN3vq1IPq5x8PAEb4x2e6EZZaZrtMU7blB6gchYMh4ZaVYSny/YepyY8ymxFxkX59Ulcq+KJ4peQUdBJ/mIm4TzzvXD4ID0OQ+Z6L8qHVE+yqt/2SMyTKWK+og+Iyy9n4AQ/08BRNoflZrfQyAscyKXUkb82Pkj/3fSQXB5wqUAwDfN+Y9Rc6tY/4++voKfWkFQbARPO/s3IJEx9eu16iMFiNuDq31CbU59Z1ksQfsWIt9OwplzN1ce7gp/cRqNWSgA/A8A2Gtjmps7eYAoLX+mCM6abAgpZ+Hq6P1dOILuO5FlVi7LZoH/B43ypgbMOxhKMBZtfGxY93fHVw7/5Qa0qIZSgYZcS0AILF2KFuxCoJbCgdAZ74ZRDcR8ytMtBqAtyI9F/UP4GIAzfK9Jcz8Y0rTypxat/2dsbU7AfB8Y+YP34iAVXLqzSJk4HkAjoCB2NqhlrYy5mECKpzWO9bJC28Q8KoDEmJ+PI6iwcQIyytjnkLBTNcMPEZpRmoCsDaAGRht3B/2gMMc0Eo0wqUEvOJqL1kfOHgw1Kqv9Q+RBk4CABDzMiCa3kLdQDr88HkGLAGXxtaeUav7gyA6w0tD5q6O1t0JrzHwmgfcGVk7NFVfGfMgAxUCVkLrWcQtAwswLHTliM+OKIp+poxZD50Lcs4MHNPI8AIA0oHco4altYAHYM1h1nTIt8hEK7Spx3aNDGvdNHFjO2Dmy4iosPH1xj/KYR0q6OtrLGHEtRgBA+cQUMz4Eu0FYC+mxu9JJjqXnLNIv+Ia4RNR5vhiYr5g6O/23CfBsABH9Z1g6/P4Z3cuw+lnOkAUDlv+HwDfB/DFIpXUhvw1cyW97gHv8zwvdK6lsLfTOL2HAIx0+XhE03n4OW5yXZvgA1iL0rqHElV6nrc8M28wjjGDKxKwIhM9W7d8fRp735KpD3Y1ymDE1p5GRJ9D+wOVJMT8/xJrJ91g/6lCEkVXI02DXYSHrLUdSbfSLpIw/AOA3PTbRSDgV0kYfpqAsfq4746i6Oft0DIZqOULvLMNVS2G5x062e+lqUhmay0Kw+9QOu3z7nbshIHH4NyeURR12qXR7Sym/NRBg1zcUSVtIrb2SDDPHUcVIYDTI2s/ACCKouguJjqvlQoYeMJPI8F1U2S0gVjrgwH8fRx1POQBs2oxuYU207C3M4qi/wOwkx8EBxLzxwHsg9Z9J/cT84/iKLoQBdPOw7n725wie+hzjJhfYKJ72lj3cCYkOwQTXQDmGXnlYt/PjYhWgH8CeLEN9TRjII6id+gg+BQDp6Nolgrmlxi4Uvv+OdVqdcQ09SQMP66MeQXA59H8094RcFWs9aeTJUvqj7O998nooXX3ovXnqRmjk9YuWfJiDOyujPkCmD+bGXApmweI+cdRFJ2HYS+kKlGoxnFeiGjoq43T1GRtO8dE9Mzg3475JWpD3QzUT/CZh9Y79BvSiqNlBd+YtxGwAwEzOfUFrsCpX6sKooVgXsjMT4HojkSp27B06YJ2CRV6AuMbcwCliVe3BtEqNZ/m6wBeIOAVJppHzv05iqJ/IG8qcF/fmsq5I2qJXGcw0EepP/Q5AHd4wDXjybU3xQh8Y/YnYDcAWwFYhYHlKW0ULSRgPojuJea5PXROBEEQBEEQBEEQBEEQBEEQBEEQBEEQBEEQhMKUFqVLEISuoaK0Psv3/TWccw0DEwmCIAjtg7QxVyhjWBnz1bLFCIIg9ATKmG8oYxb4Wn+wbC1TjebBlAVBEBqzChEtiMJwY6ShPIUWGFMct17BGLMFA18qUtYBVybWDgWbVsacTcC6hXZEdGsUhj9sVkRrvTPSaHNg574fx/HtWeVUpbIPOffRIrtl534Yx/GtQ9tqfQ4RNQwh6Yhe9Zz7RxRFFyEjuppvzOFe8Qy3A5G1HypYdhRa6+1B9F9FyjqiS5IwvCZjFfnGHEzA8QB2RRpH+nUw38VEFybWXoZh8VeH7futIDqlyS4tA88T85VRFGVGFqsFgS8UJ8ABv06svWrwtx8EB3nMo1qajmhREoYnNKvLN+bnXkZcYkd0YRKGxXL99fWtreL4c0x00LB7/EkwXxMrdQ4GBp4rVE+PIy3fJjjPmw7n3lv7+SiAfwE4BABAdDuYFYCdAYDSINPDI/0v5jT7xgG13/cMBfVhVsS8Noh2B1Ah5vx8ZkQfY+C9AECeBwCZxpeSZAMmGtR8H5hfBtF+td83Is0usiUAMNFIg0T0OoBkWNT/eUx0FwB4zCsQ89FM1K+MOSG2dhfUZz4hGgDzAAOHIc348SoT/WZoNfN0ADvWNCwGMGbj64jWpNr5QHrun8BgolbmW0C0DIDtAYDS0Ir1xjfQxlxSO6cLAJzDwOO1l89HCLhEaf3hOIoOQxoPYthhUpQQvVaLQbEpADBwIYgsAK+WDugoJvqC1vq4KIouqNs3HPMi8rxtwTyrpvlm9rz5tb8NEa2PNLKgT8CI1EZEVHXMS4jowGGBiK4l5qfyzhsxL2JgHRDtW1v0LANziahQFhs/CN5JSXIZiPo94HIwf60m6u1MdKJKkuM5CN5f2JALQhaqUtlbGcN+EPwUgIf+/tVqHQvsG3M4kEbNry37n8w6jHm90XpVqeyjjGFtzEU5UvqUMf8ZVlcVwFuyCmqtj6+VOQVIc+kNatZab1fT9D/KGPa1PjpTcxC8VNvmayOWK7XHYF0qCBrm9dLGXFQrl9Xzvawy5lllzLiyfvjGHKKMYaX1twFQEAQbDV2bNG0LtNYfr2n9wqjtg+DcWvln0d9fH+2rXxkzr7Y+q8WMWv3HDO4TwIhIfMqYa2vrGsfBHXk/jcpBp7X+aG195teXMuas2vEVyoc3vGpljFPGsA6CEwtvpPU2ypiB2nafGLU+CD5R07u0QXoiYRgdz77QDSjgLGR8fgJAEkUXI00V04iRoTT7+1dVxizUWm8XV6t/JiC3teIb804AyxHRGQAWAgi01s1yUkWxtd9utDK29mw0iwjWIF1BHMdDhoSZG+bocmkLcARK6z/WjMhiMBfJBVaI2Jiz0OBYoij6CTJ8kcaYzYn5+NrPH2F0OMklTDRo8A6q5WMbhWtwT9S4HwCoWS6zJUtGnyeldlHGLATwliiKLkHzGMPp9s61Gs50+D1ZODUFE30daeqtBVEYjsp4HoXhjwHMB9DngFYS5PYkYnwLEIZhfTqRep7JWT+EH0V7IPUtDrp87nTD4pxmQcBRAFzk+1cMfsYzUbNP9pfQPH7yEqRGvBU8ZUzqTyR6OTHmlha2XQlEO6GWgoWB+whY1OL+s4gyDOdwGMCoa+dSV4OH9L8/ZG2YhOEtQ5UwH96SqmnTVkfNPcXAr1va1vP2Qnp/EFK/+v0ZsYDLYCUA+wEAE81BdvxqZqLBPoT9Ufc1IIxEfL5NiKvVW5F+3odNy1m7L/KzIJygjDkUdSmsI2vzHuy3ANgfRLdj6dIFpNSl8LzjAexojNnCWjvCHxilLfHcBz62dkPkpyT6+GDm4FoCSQfgao/5v/HGGy/l7QPATGXM3UgTMg61AJMouigB8lwtTUmsnQMgNzh4bO3OGGwh1iBg08GmsrW20Yt1MdKXVH+RVOW+MfOQBmcPEMerAHgSwCnNvkDq+HLNVbRZnf7tC27fUZRSG6M2KYuYG3aoEfMLg5torTeMoqgt2XC6EWn5NicG8FqBcosxLFFnJkT/BvBHpA9lYbTWhwMwYL69Uqls4Pv+c2B+EQAckOWzDVHXQdSARagzShn8zSP6LgFXEbCIgVcBzLPWFstgy7wYwFyML5VNIyIUuzavo65jkEfO7Gz22Z2+UNOO1aZ4RN8j4OcE3AbAgflZYr4RBVMTEfAgmG8Ec5GXWjHSNOhjSeG+DCqV+pE6w21F/jlLkcZdE8T4ThTMc2NrTyLmEQZTGXN9sw439ryjan+eFjv3eML82LB0MB9AZ6eIz4vC8AextSeD+ZBaRt8vK6V2L7Q10fOxtafE1h6INHsEAEBrfcJ4O9zGyVDaoSAIVmtQpg+19OAEPJ5XYRSGF8bWnhlZewQBvwXR3kz0k6KCGLgijqLPM9GIzkHfmPmNOtzyUEnyQE7fQCY6CD6snBuRhifWeihNETdJRzRsHUfGjE5tJAwhxneCiaLo0Vr+qioAMLBRw8KVynq1oUhzlOfNGPwH52bVSqzhB8HbO68aiKJoHmodi0Q0E5XKer4x783ZbBBHwB0gWggATNT4mCcC534/+Gfs3M5ZRfwg2HuoeAO/cMPqgetrf86s1XVwEAQzi2zrMT9Yuz8SpCm61mll38NYCRnjeYvAzo0en56O3b0LAIh5l0bbEvNOtT//nuOP73nE+Hae+iSJryXWzoii6H6l1G6U+kMzUc59EAAxcHm1Wv334L84jv+KWoJAYj6ug9rrJ+G8DABMNN13bhYBJ2Vt5DGPSloZWfu+OAzPRppHrbUOrDYTx/EdAK4DAEonS/TXFfHIuc/W/r4nsfY3aAEieqX25/IAAmL+csy89aiC/f2jEmhGUTQvsXYGgEVa6yPQPAFow0lSWutmk12aTa4yIDo4eys6HanLYVs/7b8YgR8EBwHYFoCD553eZB8CxCdTlOWVMR/jOB4ylMR8vDJmfQ+4rr7TCwC01sc6z9sBzGnrg3mWMubkoQLM00H04aydGWM2TYg+BeZj0p3RLGXMmrG131dKbQ/P2xVvGvXDtDGXsuddMCLFd6WyrnLu/UgnNdRE038pY+6Nff/SrFlIWuvjmGg9pJ1rAHCwMuZ1OHd7bUbdM0iTLx5DzM8S0b+Gb6+U2g2ety8TzQYzAEwfcczANAAHc9qaa9d01JWVMcc55qGOKmL+uDJmS5/o6jAM6zPQAgBiaz+ojLkewI7KmNsZONtjftR53urE/EkA+wJ4JPb9w1DXsx8EwYyE+T148zjhB8GZxPxUbO05HvMzNaeor435KQMb+8C/hleiguBziKKhFqQHHETGrDn4m4nWZeZjs7RrrXfgdOLMYekBU//w88zMazPRRzK3DYJPO+c2Qs0AM/M+ypjK4KEBmI30a+zVUecsDP+kg+BEZj6HgMuUMd+CczciNbZ7g/lUAAkxfzqqVm/O2r/wJjK9uAjTpq2h4jjz05OJzkjC8Lr65dqYy5u6FEZyXWztGYM/VBDsB+av1xeKrd1Ha30kE41q7RLRL6Iw/NHQ/tMHNNPnSMzHRVF0f/1yZcyVANbPKP+zKIp+poLg7WC+GqkRXeAB+w3vfNNaf5SJjq/fPhOipXEY7lGobBNqhvCKzJWed1Lc3AhUVBB8EswnYNi1IuBpBi6qjYce9ZKovWS+m1VhbO1uAKrKmN8DOBgAmOhnSRiOmPKtguB2MFey6qiHmM+Nouj8wd++MUcRUGhyBANfS6y9emi/Ws8FUf4QMOb/xFG0T9YqpdQu8Lz/BrAP3pwiXQUwl5i/0mhKtSAI42NaEAQbovnn8FRklSAINkZ/f6MOuNbp61urrfVNPipBEMyoVCoboGCcCuFNpOUrTAr8IDgYzJmtyUGI6P/FYXjjRGkShE7y/wGut/vEvUdjQwAAAABJRU5ErkJggg==" />
                        </div>
                        <div class="col-xs-8 event">
                            <p><strong>{{ $inscription->slot->name ?? '' }}</strong></p>
                            <h1>{{ $inscription->session->event->name }}</h1>
                            <div class="row">
                                <div class="col-xs-12">
                                    <p class="confirmation-code text-right">{{ $inscription->cart->confirmation_code }}</p>
                                    <p>{{ $inscription->session->starts_on->translatedFormat('d/m/Y H:i') }}</p>
                                    <p>{{ sprintf("%s - %s €", $inscription->getRateName(), number_format($inscription->price_sold, 2)) }}</p>
                                    @if(isset($inscription->group_pack->pack->name))
                                    <p><i>{{ $inscription->group_pack->pack->name }}</i></p>
                                    @endif
                                </div>
                            </div>

                            <div class="bar-code" style="background-color:white;">
                                <img width="50%" style="border: 1px solid white;" src="{{ sprintf('data:image/png;base64,%s',  (DNS1D::getBarcodePNG(strtoupper($inscription->barcode), "C39", 3, 90))) }}" class="img-fluid img-responsive" />
                                <span>{{ strtoupper($inscription->barcode) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-xs-7 conditions">
                            <p>No es permetrà l'entrada iniciat l'espectacle</p>
                            <p>En cas que s'anul·li l'acte es tornarà l'import de l'entrada</p>
                            <p>L'organització es reserva el dret d'alterar o modificar el programa de l'acte</p>
                        </div>
                        <div class="col-xs-5 conditions text-right">
                            <p class="mt-2"><strong>Ajuntament de Torelló</strong></p>
                            <p>c/ Ges d ́Avall, 5 08570-TORELLÓ NIF P-0828500-I</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if($inscription->getBanner() != NULL)
            @include('core.inscription.ticket.banner', ['banner' => $inscription->getBanner()])
        @endif
    </div>
</body>
</html>
