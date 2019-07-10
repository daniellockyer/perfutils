#![feature(proc_macro_hygiene, decl_macro, type_ascription)]

#[macro_use]
extern crate rocket;

use std::io::{self, Cursor, Seek, SeekFrom};
use inferno::collapse::Collapse;
use rocket::Data;

#[post("/", data = "<data>")]
fn upload(data: Data) -> io::Result<String> {
    let xd_opts = inferno::collapse::xdebug::Options;
    let mut fg_opts = inferno::flamegraph::Options::default();

    let mut buff = Cursor::new(Vec::new());
    let mut buff2 = Cursor::new(Vec::new());
    let mut buff3 = Cursor::new(Vec::new());

    data.stream_to(&mut buff)?;

    buff.seek(SeekFrom::Start(0))?;
    inferno::collapse::xdebug::Folder::from(xd_opts).collapse(buff, &mut buff2)?;

    buff2.seek(SeekFrom::Start(0))?;
    let _ = inferno::flamegraph::from_reader(&mut fg_opts, buff2, &mut buff3);

    let inner = buff3.into_inner();

    if let Ok(u) = std::str::from_utf8(&inner) {
        Ok(u.to_owned())
    } else {
        Ok("error".to_owned())
    }
}

fn main() {
    rocket::ignite()
        .mount("/upload", routes![upload]).launch();
}
