INSERT INTO brygady (linia_id, nazwa) 
VALUES ( (SELECT id FROM linie WHERE nazwa='107' LIMIT 1), '107/1' )
     , ( (SELECT id FROM linie WHERE nazwa='107' LIMIT 1), '107/2' )
     , ( (SELECT id FROM linie WHERE nazwa='116' LIMIT 1), '116/1' )
     , ( (SELECT id FROM linie WHERE nazwa='116' LIMIT 1), '116/2' );